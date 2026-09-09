<?php

namespace App\Console\Commands;

use App\Models\Hotel;
use App\Services\AttendanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MarkAbsentEmployees extends Command
{
    protected $signature = 'attendance:mark-absent {--date= : The date to check (Y-m-d)}';
    protected $description = 'Mark employees as absent if they have a schedule but no attendance record for the given date';

    public function handle(AttendanceService $attendanceService): int
    {
        $date = $this->option('date') ?? now()->format('Y-m-d');

        $this->info("Checking absenteeism for date: {$date}");

        $hotels = Hotel::where('is_active', true)->get();
        $totalCount = 0;

        foreach ($hotels as $hotel) {
            // Set active hotel for global scope
            session(['active_hotel_id' => $hotel->id]);

            try {
                $count = $attendanceService->markAbsentees($date);
                if ($count > 0) {
                    $this->info("Hotel {$hotel->name}: {$count} employee(s) marked as absent.");
                }
                $totalCount += $count;
            } catch (\Exception $e) {
                $this->error("Error processing hotel {$hotel->name}: {$e->getMessage()}");
                Log::error('MarkAbsentEmployees error', [
                    'hotel_id' => $hotel->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        session()->forget('active_hotel_id');

        $this->info("Total: {$totalCount} employee(s) marked as absent.");

        return Command::SUCCESS;
    }
}
