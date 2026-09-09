<?php

namespace App\Console\Commands;

use App\Models\Hotel;
use App\Models\Inventory;
use App\Models\InventoryCategory;
use Illuminate\Console\Command;

class ImportLegacyItems extends Command
{
    protected $signature = 'legacy:import-items
        {--hotel= : Hotel ID tujuan import}
        {--file=20260408195827/csv/items.csv : Path file items CSV}
        {--truncate : Kosongkan inventory sebelum import}';

    protected $description = 'Import legacy items into inventory for POS usage';

    public function handle(): int
    {
        $hotel = $this->resolveHotel();
        if (!$hotel) {
            $this->error('No active hotel found.');
            return self::FAILURE;
        }

        $this->info("Target hotel: [{$hotel->id}] {$hotel->name}");

        if ($this->option('truncate')) {
            Inventory::where('hotel_id', $hotel->id)->delete();
        }

        $path = base_path($this->option('file'));
        if (!is_file($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $this->info('Importing items...');
        $count = 0;

        foreach ($this->yieldCsv($path) as $row) {
            $name = $row['NAMA'] ?? null;
            if (!$name) continue;

            $isJasa = filter_var($row['JASA'] ?? false, FILTER_VALIDATE_BOOLEAN);
            
            Inventory::updateOrCreate(
                ['hotel_id' => $hotel->id, 'name' => $name],
                [
                    'category' => $isJasa ? 'Service' : $this->guessCategory($name),
                    'stock' => (int)($row['SALDOAWAL'] ?? 0),
                    'min_stock' => 5,
                    'unit' => $row['SATUAN'] ?? 'pcs',
                    'price_per_unit' => (float)($row['HARGAJUAL'] ?? 0),
                    'is_active' => filter_var($row['AKTIF'] ?? true, FILTER_VALIDATE_BOOLEAN),
                ]
            );
            $count++;
        }

        $this->info("Imported {$count} items successfully.");
        return self::SUCCESS;
    }

    protected function resolveHotel()
    {
        $hotelId = $this->option('hotel');
        return $hotelId ? Hotel::find($hotelId) : Hotel::where('is_active', true)->first();
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

    protected function guessCategory(string $name): string
    {
        $name = strtoupper($name);
        if (str_contains($name, 'DRINK') || str_contains($name, 'TEH') || str_contains($name, 'WATER')) return 'Beverage';
        if (str_contains($name, 'SNACK') || str_contains($name, 'SNAK') || str_contains($name, 'ROSTA')) return 'Food/Snack';
        return 'General';
    }
}
