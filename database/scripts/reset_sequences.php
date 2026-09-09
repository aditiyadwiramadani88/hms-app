<?php

/**
 * Reset all PostgreSQL sequences to MAX(id) + 1.
 * Run this after data migration to prevent primary key collisions.
 * 
 * Usage: php artisan tinker database/scripts/reset_sequences.php
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (config('database.default') !== 'pgsql') {
    echo "This script is only for PostgreSQL.\n";
    return;
}

$tables = DB::select("
    SELECT table_name 
    FROM information_schema.tables 
    WHERE table_schema = 'public' 
    AND table_type = 'BASE TABLE'
    AND table_name NOT IN ('migrations', 'failed_jobs', 'personal_access_tokens')
");

echo "Starting sequence reset...\n";

foreach ($tables as $table) {
    $tableName = $table->table_name;
    
    if (Schema::hasColumn($tableName, 'id')) {
        $maxId = DB::table($tableName)->max('id') ?: 0;
        $nextId = $maxId + 1;
        
        try {
            // Get sequence name - Laravel usually uses table_name_id_seq
            $seqName = "{$tableName}_id_seq";
            
            // Check if sequence exists
            $seqExists = DB::select("SELECT 1 FROM pg_class WHERE relname = ?", [$seqName]);
            
            if ($seqExists) {
                DB::statement("SELECT setval(?, ?, false)", [$seqName, $nextId]);
                echo "SUCCESS: Reset {$seqName} to {$nextId}\n";
            } else {
                // Try to find sequence via pg_get_serial_sequence
                $actualSeq = DB::select("SELECT pg_get_serial_sequence(?, 'id') as seq", [$tableName])[0]->seq;
                if ($actualSeq) {
                    DB::statement("SELECT setval(?, ?, false)", [$actualSeq, $nextId]);
                    echo "SUCCESS: Reset {$actualSeq} to {$nextId}\n";
                }
            }
        } catch (\Exception $e) {
            echo "ERROR: Failed to reset sequence for {$tableName}: " . $e->getMessage() . "\n";
        }
    }
}

echo "Sequence reset completed.\n";
