<?php

namespace App\Console\Commands;

use App\Models\GuestCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportGuestCategoriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:guest-categories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import guest categories from the legacy anggota.csv file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = base_path('20260408195827/csv/anggota.csv');

        if (!file_exists($filePath)) {
            $this->error('The file "20260408195827/csv/anggota.csv" was not found.');
            return 1;
        }

        $header = true;
        $processedCount = 0;
        $skippedCount = 0;

        if (($handle = fopen($filePath, 'r')) !== FALSE) {
            $this->info('Starting guest category import...');
            
            while (($row = fgetcsv($handle, 1000, ',')) !== FALSE) {
                if ($header) {
                    $header = false;
                    continue;
                }

                if (count($row) < 2 || empty($row[0]) || empty($row[1])) {
                    $this->warn('Skipping malformed or empty row: ' . implode(',', $row));
                    $skippedCount++;
                    continue;
                }

                try {
                    GuestCategory::updateOrCreate(
                        ['code' => $row[0]], // Match by unique code
                        ['name' => $row[1]]
                    );
                    $processedCount++;
                } catch (\Exception $e) {
                    $this->error('Failed to import category with code ' . $row[0] . '. Error: ' . $e->getMessage());
                    Log::error('Guest Category Import Failed: ' . $e->getMessage(), ['row' => $row]);
                    $skippedCount++;
                }
            }

            fclose($handle);
            $this->info("Guest category import complete.");
            $this->info("Successfully processed: {$processedCount} categories.");
            $this->info("Skipped: {$skippedCount} rows.");

        } else {
            $this->error('Could not open the CSV file.');
            return 1;
        }

        return 0;
    }
}
