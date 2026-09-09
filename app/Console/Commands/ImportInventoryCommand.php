<?php

namespace App\Console\Commands;

use App\Models\Inventory;
use App\Models\InventoryCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportInventoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:inventory';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import inventory items from the legacy items.csv file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = base_path('20260408195827/csv/items.csv');

        if (!file_exists($filePath)) {
            $this->error('The file "20260408195827/csv/items.csv" was not found.');
            return 1;
        }

        // Ensure a default category exists
        $defaultCategory = InventoryCategory::firstOrCreate(
            ['name' => 'Uncategorized'],
            ['is_active' => true]
        );

        $header = true;
        $processedCount = 0;
        $skippedCount = 0;

        if (($handle = fopen($filePath, 'r')) !== FALSE) {
            $this->info('Starting inventory import...');

            while (($row = fgetcsv($handle, 1000, ',')) !== FALSE) {
                if ($header) {
                    $header = false;
                    continue;
                }

                if (count($row) < 8 || empty($row[0]) || empty($row[1])) {
                    $this->warn('Skipping malformed or empty row: ' . implode(',', $row));
                    $skippedCount++;
                    continue;
                }
                
                // Map CSV to DB columns
                // KODE,NAMA,HARGABELI,HARGAJUAL,SATUAN,JASA,SALDOAWAL,AKTIF
                $inventoryData = [
                    'name' => $row[1],
                    'category_id' => $defaultCategory->id,
                    'stock' => (int)($row[6] ?? 0),
                    'min_stock' => 0, // Default value
                    'unit' => $row[4] ?? 'pcs',
                    'price_per_unit' => (float)($row[3] ?? 0), // Using HARGAJUAL as price
                    'is_active' => strtolower($row[7] ?? 'true') === 'true',
                ];

                try {
                    // Using name as the unique key as code might not be present in the new system's table
                    Inventory::updateOrCreate(
                        ['name' => $row[1]], 
                        $inventoryData
                    );
                    $processedCount++;
                } catch (\Exception $e) {
                    $this->error('Failed to import item: ' . $row[1] . '. Error: ' . $e->getMessage());
                    Log::error('Inventory Import Failed: ' . $e->getMessage(), ['row' => $row]);
                    $skippedCount++;
                }
            }

            fclose($handle);
            $this->info("Inventory import complete.");
            $this->info("Successfully processed: {$processedCount} items.");
            $this->info("Skipped: {$skippedCount} rows.");

        } else {
            $this->error('Could not open the CSV file.');
            return 1;
        }

        return 0;
    }
}
