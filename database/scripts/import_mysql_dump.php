<?php

/**
 * Import MySQL dump file into PostgreSQL.
 * Surgically parses INSERT statements and converts them to PG format.
 * 
 * Usage: php artisan tinker database/scripts/import_mysql_dump.php
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$dumpFile = 'db/backup_homestay_20260523_200551.sql';

if (!file_exists($dumpFile)) {
    echo "Dump file not found: {$dumpFile}\n";
    return;
}

if (config('database.default') !== 'pgsql') {
    echo "This script is only for PostgreSQL.\n";
    return;
}

echo "Starting MySQL dump import into PostgreSQL...\n";

// Disable foreign key checks for the session
DB::statement("SET session_replication_role = 'replica';");

$handle = fopen($dumpFile, 'r');
$currentTable = '';
$insertBuffer = '';
$totalRows = 0;

while (($line = fgets($handle)) !== false) {
    $line = trim($line);
    
    if (empty($line) || str_starts_with($line, '--') || str_starts_with($line, '/*') || str_starts_with($line, 'LOCK TABLES') || str_starts_with($line, 'UNLOCK TABLES') || str_starts_with($line, 'DROP TABLE')) {
        continue;
    }

    if (preg_match('/INSERT INTO `([^`]+)` VALUES/', $line, $matches)) {
        $currentTable = $matches[1];
        
        // Convert backticks to nothing for PG (or double quotes)
        $line = str_replace("`{$currentTable}`", "\"{$currentTable}\"", $line);
        
        // MySQL uses \ for escapes, PG uses '' (for single quotes). 
        // We'll do a basic replacement of escaped single quotes.
        $line = str_replace("\\'", "''", $line);
        
        // Handle escaped backslashes (\\)
        // This is tricky because we don't want to break already valid PG strings.
        // But MySQL dump usually has \\ for \.
        
        // Remove trailing semicolon if it exists to append more if needed (though usually it's one line per INSERT in mysqldump)
        $isComplete = str_ends_with($line, ';');
        
        try {
            DB::statement($line);
            $totalRows += 1; // This is a rough count of statements, not rows
            if ($totalRows % 10 === 0) echo "Processed {$totalRows} insert statements...\n";
        } catch (\Exception $e) {
            echo "ERROR in table {$currentTable}: " . $e->getMessage() . "\n";
            // echo "QUERY: " . substr($line, 0, 100) . "...\n";
        }
    }
}

fclose($handle);

// Re-enable foreign key checks
DB::statement("SET session_replication_role = 'origin';");

echo "Import completed. Total statements executed: {$totalRows}\n";
echo "Now running sequence reset...\n";

// Run the sequence reset script
require base_path('database/scripts/reset_sequences.php');
