<?php

namespace App\Console\Commands;

use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportLegacyRooms extends Command
{
    protected $signature = 'legacy:import-rooms
        {--hotel= : Hotel ID tujuan import}
        {--room-file=20260408195827/csv/room.csv : Path file room CSV}
        {--type-file=20260408195827/csv/type.csv : Path file type CSV}
        {--truncate : Kosongkan data kamar yang ada sebelum import}';

    protected $description = 'Import legacy room and room type data from CSV';

    public function handle(): int
    {
        $hotel = $this->resolveHotel();
        if (!$hotel) {
            $this->error('No active hotel found.');
            return self::FAILURE;
        }

        $this->info("Target hotel: [{$hotel->id}] {$hotel->name}");

        if ($this->option('truncate')) {
            $this->warn('Truncating rooms and room types...');
            // Use DB to bypass potential foreign key constraints or global scopes if needed
            Schema::disableForeignKeyConstraints();
            Room::where('hotel_id', $hotel->id)->delete();
            RoomType::where('hotel_id', $hotel->id)->delete();
            Schema::enableForeignKeyConstraints();
        }

        // Phase 1: Import Room Types
        $this->info('Importing room types...');
        $typeMap = $this->importRoomTypes($hotel->id);

        // Phase 2: Import Rooms
        $this->info('Importing rooms...');
        $this->importRooms($hotel->id, $typeMap);

        $this->info('Import completed successfully!');
        return self::SUCCESS;
    }

    protected function resolveHotel()
    {
        $hotelId = $this->option('hotel');
        return $hotelId ? Hotel::find($hotelId) : Hotel::where('is_active', true)->first();
    }

    protected function importRoomTypes(int $hotelId): array
    {
        $path = base_path($this->option('type-file'));
        $map = [];
        
        foreach ($this->yieldCsv($path) as $row) {
            $legacyCode = $row['KODE'] ?? null;
            $name = $row['NAMA'] ?? null;
            
            if (!$legacyCode || !$name) continue;

            $type = RoomType::updateOrCreate(
                ['hotel_id' => $hotelId, 'name' => $name],
                [
                    'description' => $row['KETERANGAN'] ?? null,
                    'amenities' => $row['KETERANGAN'] ?? null,
                    'base_price' => $this->guessPrice($name),
                    'max_guests' => 2,
                    'is_active' => true
                ]
            );
            
            $map[$legacyCode] = $type->id;
            $this->info("Type: {$name} imported.");
        }
        return $map;
    }

    protected function importRooms(int $hotelId, array $typeMap): void
    {
        $path = base_path($this->option('room-file'));
        
        foreach ($this->yieldCsv($path) as $row) {
            $roomNumber = $row['ROOM'] ?? null;
            $legacyTypeCode = $row['KODETYPE'] ?? null;
            
            if (!$roomNumber) continue;

            Room::updateOrCreate(
                ['hotel_id' => $hotelId, 'room_number' => $roomNumber],
                [
                    'room_type_id' => $typeMap[$legacyTypeCode] ?? null,
                    'floor' => $this->extractFloor($row['POSISI'] ?? ''),
                    'status' => 'Available',
                    'notes' => $row['POSISI'] ?? null
                ]
            );
            $this->info("Room: {$roomNumber} imported.");
        }
    }

    protected function yieldCsv(string $path): \Generator
    {
        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            yield array_combine($headers, $row);
        }
        fclose($handle);
    }

    protected function extractFloor(string $posisi): string
    {
        if (preg_match('/LANTAI\s*(\d+)/i', $posisi, $matches)) {
            return $matches[1];
        }
        return '1';
    }

    protected function guessPrice(string $name): float
    {
        $name = strtoupper($name);
        if (str_contains($name, 'VIP 1')) return 400000;
        if (str_contains($name, 'VIP 2')) return 350000;
        if (str_contains($name, 'DELUXE')) return 300000;
        if (str_contains($name, 'SUPERIOR')) return 250000;
        return 200000; // Default
    }
}
