<?php

namespace App\Console\Commands;

use App\Models\GuestCategory;
use App\Models\RoomRate;
use App\Models\RoomType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportRoomRatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:room-rates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import room rates from the legacy rate.csv file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = base_path('20260408195827/csv/rate.csv');

        if (!file_exists($filePath)) {
            $this->error('The file "20260408195827/csv/rate.csv" was not found.');
            return 1;
        }

        // Cache categories and types to reduce DB queries
        $guestCategories = GuestCategory::pluck('id', 'code')->all();
        $roomTypes = RoomType::pluck('id', 'id')->all(); // Assuming legacy KODETYPE maps directly to new ID

        $header = true;
        $processedCount = 0;
        $skippedCount = 0;

        if (($handle = fopen($filePath, 'r')) !== FALSE) {
            $this->info('Starting room rate import...');

            while (($row = fgetcsv($handle, 1000, ',')) !== FALSE) {
                if ($header) {
                    $header = false;
                    continue;
                }

                if (count($row) < 4) {
                    $this->warn('Skipping malformed row.');
                    $skippedCount++;
                    continue;
                }

                $guestCategoryCode = $row[0];
                $roomTypeId = $row[1];
                $price = (float)$row[3];
                
                if (!isset($guestCategories[$guestCategoryCode])) {
                    $this->warn("Skipping rate. Guest Category with code '{$guestCategoryCode}' not found.");
                    $skippedCount++;
                    continue;
                }
                
                if (!isset($roomTypes[$roomTypeId])) {
                     $this->warn("Skipping rate. Room Type with ID '{$roomTypeId}' not found.");
                    $skippedCount++;
                    continue;
                }

                $guestCategoryId = $guestCategories[$guestCategoryCode];

                try {
                    RoomRate::updateOrCreate(
                        [
                            'room_type_id' => $roomTypeId,
                            'guest_category_id' => $guestCategoryId,
                        ],
                        [
                            'name' => "Legacy Rate",
                            'start_date' => '2024-01-01', // Default start date
                            'end_date' => '2099-12-31',   // Default end date
                            'price' => $price,
                            'is_locked' => false,
                        ]
                    );
                    $processedCount++;
                } catch (\Exception $e) {
                    $this->error('Failed to import rate. Error: ' . $e->getMessage());
                    Log::error('Room Rate Import Failed: ' . $e->getMessage(), ['row' => $row]);
                    $skippedCount++;
                }
            }

            fclose($handle);
            $this->info("Room rate import complete.");
            $this->info("Successfully processed: {$processedCount} rates.");
            $this->info("Skipped: {$skippedCount} rows.");

        } else {
            $this->error('Could not open the CSV file.');
            return 1;
        }

        return 0;
    }
}
