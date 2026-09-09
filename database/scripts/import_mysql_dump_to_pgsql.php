<?php

/**
 * Import data from MySQL dump file into PostgreSQL.
 * 
 * Prerequisites:
 * 1. Run `php artisan migrate:fresh` on PostgreSQL first to create schema
 * 2. Then run this script to import data
 * 
 * Usage: php artisan tinker database/scripts/import_mysql_dump_to_pgsql.php
 * 
 * Or from command line:
 *   cd /path/to/project
 *   php -r "require 'vendor/autoload.php'; $app = require_once 'bootstrap/app.php'; $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class); $kernel->bootstrap(); require 'database/scripts/import_mysql_dump_to_pgsql.php';"
 */

use Illuminate\Support\Facades\DB;

$dumpFile = base_path('db/backup_homestay_20260523_200551.sql');

if (!file_exists($dumpFile)) {
    echo "ERROR: Dump file not found at: {$dumpFile}\n";
    return;
}

if (config('database.default') !== 'pgsql') {
    echo "ERROR: Default database connection is not pgsql. Check your .env\n";
    return;
}

echo "=== MySQL Dump to PostgreSQL Data Importer ===\n";
echo "Source: {$dumpFile}\n";
echo "Target: PostgreSQL ({$_ENV['DB_DATABASE']})\n\n";

// Disable foreign key checks (try superuser method first, fallback to per-table disable)
$useTruncateCascade = false;
try {
    DB::statement("SET session_replication_role = 'replica';");
} catch (\Exception $e) {
    echo "NOTE: Cannot set session_replication_role (not superuser). Will disable triggers per-table.\n";
    $useTruncateCascade = true;
}

$handle = fopen($dumpFile, 'r');
if (!$handle) {
    echo "ERROR: Cannot open dump file\n";
    return;
}

$currentTable = null;
$insertBuffer = '';
$totalRows = 0;
$tableRows = [];
$errors = [];
$skippedTables = ['migrations']; // Don't import migrations table

echo "Parsing and importing...\n";

while (($line = fgets($handle)) !== false) {
    $line = rtrim($line);
    
    // Skip MySQL-specific comments and settings
    if (str_starts_with($line, '/*') || str_starts_with($line, '--') || empty($line)) {
        continue;
    }
    
    // Detect LOCK/UNLOCK (skip)
    if (str_starts_with($line, 'LOCK TABLES') || str_starts_with($line, 'UNLOCK TABLES')) {
        continue;
    }
    
    // Detect ALTER TABLE DISABLE/ENABLE KEYS (skip)
    if (str_contains($line, 'DISABLE KEYS') || str_contains($line, 'ENABLE KEYS')) {
        continue;
    }
    
    // Detect DROP TABLE / CREATE TABLE (skip - we use Laravel migrations for schema)
    if (str_starts_with($line, 'DROP TABLE') || str_starts_with($line, 'CREATE TABLE')) {
        // Extract table name for tracking
        if (preg_match('/(?:DROP TABLE IF EXISTS|CREATE TABLE) `(\w+)`/', $line, $matches)) {
            $currentTable = $matches[1];
        }
        // Skip until we find the closing ;
        if (str_starts_with($line, 'CREATE TABLE')) {
            while (($line = fgets($handle)) !== false) {
                if (str_contains($line, ';')) break;
            }
        }
        continue;
    }
    
    // Detect INSERT statements
    if (str_starts_with($line, 'INSERT INTO')) {
        $insertBuffer = $line;
        
        // Multi-line INSERT — keep reading until we find the closing ;
        while (!str_ends_with(rtrim($insertBuffer), ';')) {
            $nextLine = fgets($handle);
            if ($nextLine === false) break;
            $insertBuffer .= $nextLine;
        }
        
        // Extract table name
        if (preg_match('/INSERT INTO `(\w+)`/', $insertBuffer, $matches)) {
            $tableName = $matches[1];
            
            if (in_array($tableName, $skippedTables)) {
                continue;
            }
            
            // Convert MySQL INSERT to PostgreSQL-compatible
            $pgInsert = convertMysqlInsertToPgsql($insertBuffer, $tableName);
            
            if ($pgInsert) {
                try {
                    if ($useTruncateCascade) {
                        DB::statement("ALTER TABLE \"{$tableName}\" DISABLE TRIGGER ALL;");
                    }
                    DB::unprepared($pgInsert);
                    if ($useTruncateCascade) {
                        DB::statement("ALTER TABLE \"{$tableName}\" ENABLE TRIGGER ALL;");
                    }
                    $rowCount = substr_count($insertBuffer, '),(') + 1;
                    $totalRows += $rowCount;
                    $tableRows[$tableName] = ($tableRows[$tableName] ?? 0) + $rowCount;
                } catch (\Exception $e) {
                    if ($useTruncateCascade) {
                        try { DB::statement("ALTER TABLE \"{$tableName}\" ENABLE TRIGGER ALL;"); } catch (\Exception $ex) {}
                    }
                    $errors[] = [
                        'table' => $tableName,
                        'error' => $e->getMessage(),
                        'sql_preview' => substr($pgInsert, 0, 200),
                    ];
                    echo "  ERROR [{$tableName}]: " . substr($e->getMessage(), 0, 100) . "\n";
                }
            }
        }
        
        $insertBuffer = '';
    }
}

fclose($handle);

// Re-enable foreign key checks
try {
    DB::statement("SET session_replication_role = 'origin';");
} catch (\Exception $e) {
    // Already handled per-table
}

// Reset sequences
echo "\n--- Resetting sequences ---\n";
foreach (array_keys($tableRows) as $tableName) {
    try {
        $maxId = DB::table($tableName)->max('id');
        if ($maxId) {
            $seqName = "{$tableName}_id_seq";
            $nextVal = $maxId + 1;
            DB::statement("SELECT setval('{$seqName}', {$nextVal}, false)");
            echo "  SEQ: {$seqName} → {$nextVal}\n";
        }
    } catch (\Exception $e) {
        // Sequence might not exist or table might not have id column
    }
}

// Summary
echo "\n=== IMPORT COMPLETE ===\n";
echo "Total rows imported: {$totalRows}\n";
echo "Tables processed: " . count($tableRows) . "\n";
echo "\nPer-table breakdown:\n";
foreach ($tableRows as $table => $count) {
    echo "  {$table}: {$count} rows\n";
}

if (!empty($errors)) {
    echo "\n=== ERRORS ({" . count($errors) . "}) ===\n";
    foreach ($errors as $err) {
        echo "  [{$err['table']}] {$err['error']}\n";
    }
    
    // Log errors to file
    $logFile = storage_path('logs/mysql_import_errors.log');
    file_put_contents($logFile, json_encode($errors, JSON_PRETTY_PRINT));
    echo "\nErrors logged to: {$logFile}\n";
}

echo "\nDone!\n";

// ============================================================
// Helper function
// ============================================================

function convertMysqlInsertToPgsql(string $sql, string $tableName): ?string
{
    // Remove MySQL backticks → PostgreSQL uses double quotes for identifiers (or none)
    $sql = str_replace('`', '"', $sql);
    
    // Fix: INSERT INTO "table" VALUES → keep as is (PG supports this)
    
    // Convert \' escaped quotes inside values (MySQL style) — already standard SQL
    // Convert \n, \r, \t inside strings
    // MySQL uses \' for escaping, PG uses '' — but in this context both work with PDO
    
    // Convert MySQL hex strings if any (0x...) — rare in dumps
    
    // Convert MySQL bit values b'0', b'1' to boolean
    $sql = preg_replace("/b'0'/", "'0'", $sql);
    $sql = preg_replace("/b'1'/", "'1'", $sql);
    
    // Remove trailing ; and add it back clean
    $sql = rtrim($sql, ";\n\r ");
    $sql .= ';';
    
    // Handle NULL vs empty string edge cases
    // MySQL dump already uses NULL keyword for null values
    
    return $sql;
}
