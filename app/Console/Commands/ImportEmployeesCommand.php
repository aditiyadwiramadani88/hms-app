<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportEmployeesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:employees';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import employees from the legacy pegawai.csv file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = base_path('20260408195827/csv/pegawai.csv');

        if (!file_exists($filePath)) {
            $this->error('The file "20260408195827/csv/pegawai.csv" was not found.');
            return 1;
        }

        $header = true;
        $processedCount = 0;
        $skippedCount = 0;

        if (($handle = fopen($filePath, 'r')) !== FALSE) {
            $this->info('Starting employee import...');
            $progressBar = $this->output->createProgressBar();
            
            // Get total line count for progress bar
            $lineCount = 0;
            while(!feof($handle)) { $lineCount += substr_count(fread($handle, 8192), "
"); }
            rewind($handle);
            
            $progressBar->start($lineCount);

            while (($row = fgetcsv($handle, 1000, ',')) !== FALSE) {
                if ($header) {
                    $header = false;
                    $progressBar->advance();
                    continue;
                }

                if (count($row) < 5) { // Basic validation for row structure
                    $this->warn('Skipping malformed row: ' . implode(',', $row));
                    $skippedCount++;
                    $progressBar->advance();
                    continue;
                }

                $employeeData = [
                    'name'      => $row[1],
                    'address'   => $row[2],
                    'phone'     => $row[3],
                    'position'  => $row[4],
                    'is_active' => strtolower($row[6] ?? 'ya') === 'ya',
                ];

                try {
                    Employee::updateOrCreate(
                        ['code' => $row[0]], // Match by unique code
                        $employeeData
                    );
                    $processedCount++;
                } catch (\Exception $e) {
                    $this->error('Failed to import employee with code ' . $row[0] . '. Error: ' . $e->getMessage());
                    Log::error('Employee Import Failed: ' . $e->getMessage(), ['row' => $row]);
                    $skippedCount++;
                }
                
                $progressBar->advance();
            }

            fclose($handle);
            $progressBar->finish();
            $this->info("
Employee import complete.");
            $this->info("Successfully processed: {$processedCount} employees.");
            $this->info("Skipped: {$skippedCount} rows.");

        } else {
            $this->error('Could not open the CSV file.');
            return 1;
        }

        return 0;
    }
}
