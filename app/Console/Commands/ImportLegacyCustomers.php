<?php

namespace App\Console\Commands;

use App\Models\CustomerType;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\LegacyCustomer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyCustomers extends Command
{
    protected $signature = 'legacy:import-customers
        {--hotel= : Hotel ID tujuan import}
        {--customer-file=20260408195827/csv/customer.csv : Path file customer CSV}
        {--customer-type-file=20260408195827/csv/anggota.csv : Path file customer type CSV}
        {--chunk=500 : Ukuran batch import}
        {--limit= : Batasi jumlah baris yang diimport (untuk testing)}
        {--offset= : Lewati sejumlah baris awal (untuk testing)}
        {--skip-staging : Lewati refresh staging table}
        {--staging-only : Hanya isi staging table, belum mapping ke guests}
        {--truncate-staging : Kosongkan staging hotel ini sebelum isi ulang}
        {--sync : Update guest yang sudah ada bila cocok dengan kode legacy/email/phone}';

    protected $description = 'Import legacy customer and customer type CSV data into staging and guests';

    protected array $stats = [
        'types_imported' => 0,
        'staged_total' => 0,
        'staged_skipped' => 0,
        'staged_failed' => 0,
        'guests_created' => 0,
        'guests_updated' => 0,
        'guests_failed' => 0,
    ];

    public function handle(): int
    {
        $hotel = $this->resolveHotel();
        if (!$hotel) {
            $this->error('No active hotel found.');
            return self::FAILURE;
        }

        $customerFile = base_path($this->option('customer-file'));
        $customerTypeFile = base_path($this->option('customer-type-file'));
        $chunkSize = max(1, (int) $this->option('chunk'));

        if (!is_file($customerFile)) {
            $this->error("Customer CSV not found: {$customerFile}");
            return self::FAILURE;
        }

        if (!is_file($customerTypeFile)) {
            $this->error("Customer type CSV not found: {$customerTypeFile}");
            return self::FAILURE;
        }

        $this->info("Target hotel: [{$hotel->id}] {$hotel->name}");

        // Phase 1: Import Customer Types
        $this->info('Importing customer types...');
        $typeMap = $this->importCustomerTypes($hotel->id, $customerTypeFile);

        // Phase 2: Staging
        if (!$this->option('skip-staging')) {
            $this->info('Staging legacy customers...');
            $this->stageLegacyCustomers($hotel->id, $customerFile, $chunkSize);
        }

        if ($this->option('staging-only')) {
            $this->printSummary();
            return self::SUCCESS;
        }

        // Phase 3: Sync to Guests
        $this->info('Syncing guests from staging...');
        $this->syncGuestsFromStaging($hotel->id, $typeMap, $chunkSize, (bool) $this->option('sync'));

        $this->printSummary();

        return self::SUCCESS;
    }

    protected function resolveHotel(): ?Hotel
    {
        $hotelId = $this->option('hotel');
        if ($hotelId) {
            return Hotel::find($hotelId);
        }
        return Hotel::where('is_active', true)->orderBy('id')->first();
    }

    protected function importCustomerTypes(int $hotelId, string $path): array
    {
        $map = [];
        foreach ($this->yieldCsv($path) as $row) {
            $legacyCode = $this->normalizeString($row['KODE'] ?? null);
            $name = $this->normalizeString($row['NAMA'] ?? null);

            if (!$legacyCode || !$name) {
                continue;
            }

            try {
                $type = CustomerType::updateOrCreate(
                    ['hotel_id' => $hotelId, 'legacy_code' => $legacyCode],
                    ['name' => $name, 'description' => 'Imported from legacy anggota.csv', 'is_active' => true]
                );
                $map[$legacyCode] = $type->id;
                $this->stats['types_imported']++;
            } catch (\Exception $e) {
                $this->warn("Failed to import type {$legacyCode}: ".$e->getMessage());
            }
        }
        return $map;
    }

    protected function stageLegacyCustomers(int $hotelId, string $path, int $chunkSize): void
    {
        if ($this->option('truncate-staging')) {
            LegacyCustomer::where('hotel_id', $hotelId)->delete();
        }

        $payload = [];
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $offset = $this->option('offset') ? (int) $this->option('offset') : 0;
        $count = 0;
        $read = 0;

        foreach ($this->yieldCsv($path) as $row) {
            $read++;
            if ($read <= $offset) continue;
            
            if ($limit && $count + count($payload) >= $limit) {
                break;
            }

            $legacyCode = $this->normalizeString($row['KODE'] ?? null);
            if (!$legacyCode) {
                $this->stats['staged_skipped']++;
                continue;
            }

            $payload[] = [
                'hotel_id' => $hotelId,
                'source_file' => basename($path),
                'legacy_customer_code' => $legacyCode,
                'name' => $this->normalizeString($row['NAMA'] ?? null),
                'company_name' => $this->normalizeString($row['NAMAINST'] ?? null),
                'citizenship_code' => $this->normalizeCitizenship($row['WN'] ?? null),
                'address' => $this->normalizeString($row['ALAMAT'] ?? null),
                'id_number' => $this->normalizeIdNumber($row['NOID'] ?? null),
                'phone' => $this->normalizePhone($row['TELP'] ?? null),
                'legacy_customer_type_code' => $this->normalizeString($row['KODEANGGT'] ?? null),
                'legacy_stay_type_days' => $this->normalizeInteger($row['HARI'] ?? null),
                'vehicle_number' => $this->normalizeString($row['NOPOL'] ?? null),
                'raw_payload' => json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'imported_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($payload) >= $chunkSize) {
                $this->upsertLegacyCustomers($payload);
                $count += count($payload);
                $this->stats['staged_total'] += count($payload);
                $this->info("Staged {$count} rows...");
                $payload = [];
            }
        }

        if ($payload !== []) {
            $this->upsertLegacyCustomers($payload);
            $this->stats['staged_total'] += count($payload);
            $this->info("Staged Final batch: ".count($payload)." rows.");
        }
    }

    protected function normalizePhone(?string $value): ?string
    {
        $val = $this->normalizeString($value);
        if (!$val) return null;
        
        // Pecah berdasarkan pemisah umum dan ambil yang pertama valid
        $parts = preg_split('/[\/,&]|dan|or/i', $val);
        foreach ($parts as $part) {
            $cleaned = preg_replace('/[^0-9]/', '', $part);
            if (strlen($cleaned) >= 7) {
                return $cleaned;
            }
        }
        
        return preg_replace('/[^0-9]/', '', $val) ?: null;
    }

    protected function normalizeIdNumber(?string $value): ?string
    {
        $val = $this->normalizeString($value);
        if (!$val) return null;
        
        // Hapus label umum
        $cleaned = preg_replace('/(SIM\s*[A-C]?|KTP|PASPOR|ID|NIK)\s*[-:]?\s*/i', '', $val);
        $cleaned = preg_replace('/\s*[-:]?\s*(SIM\s*[A-C]?|KTP|PASPOR|ID|NIK)$/i', '', $cleaned);
        
        // Hapus spasi dan titik
        $cleaned = preg_replace('/[\s.]/', '', $cleaned);
        
        return $cleaned ?: null;
    }

    protected function upsertLegacyCustomers(array $payload): void
    {
        try {
            DB::table('legacy_customers')->upsert(
                $payload,
                ['hotel_id', 'legacy_customer_code'],
                [
                    'source_file', 'name', 'company_name', 'citizenship_code', 'address',
                    'id_number', 'phone', 'legacy_customer_type_code', 'legacy_stay_type_days',
                    'vehicle_number', 'raw_payload', 'imported_at', 'updated_at'
                ]
            );
        } catch (\Exception $e) {
            $this->error("\nUpsert failed: ".$e->getMessage());
            $this->stats['staged_failed'] += count($payload);
        }
    }

    protected function syncGuestsFromStaging(int $hotelId, array $typeMap, int $chunkSize, bool $sync): void
    {
        $totalStaged = LegacyCustomer::where('hotel_id', $hotelId)->count();
        $this->info("Total rows in staging: {$totalStaged}");
        $processed = 0;

        LegacyCustomer::where('hotel_id', $hotelId)
            ->orderBy('id')
            ->chunk($chunkSize, function ($rows) use ($hotelId, $typeMap, $sync, &$processed) {
                foreach ($rows as $legacyCustomer) {
                    try {
                        $this->processGuestSync($hotelId, $legacyCustomer, $typeMap, $sync);
                    } catch (\Exception $e) {
                        $this->stats['guests_failed']++;
                        $this->warn("\nFailed guest sync [{$legacyCustomer->legacy_customer_code}]: ".$e->getMessage());
                    }
                }
                $processed += count($rows);
                $this->info("Synced {$processed} guests...");
            });
    }

    protected function processGuestSync(int $hotelId, LegacyCustomer $legacyCustomer, array $typeMap, bool $sync): void
    {
        $attributes = [
            'hotel_id' => $hotelId,
            'customer_type_id' => $typeMap[$legacyCustomer->legacy_customer_type_code] ?? null,
            'legacy_customer_code' => $legacyCustomer->legacy_customer_code,
            'name' => $legacyCustomer->name ?: 'Guest '.$legacyCustomer->legacy_customer_code,
            'phone' => $legacyCustomer->phone ?: '-',
            'company_name' => $legacyCustomer->company_name,
            'vehicle_number' => $legacyCustomer->vehicle_number,
            'legacy_stay_type_days' => $legacyCustomer->legacy_stay_type_days,
            'id_number' => $legacyCustomer->id_number,
            'identity_type' => $legacyCustomer->id_number ? 'legacy' : null,
            'citizenship_code' => $legacyCustomer->citizenship_code,
            'address' => $legacyCustomer->address,
            'nationality' => $this->mapNationality($legacyCustomer->citizenship_code),
        ];

        $guest = Guest::withoutGlobalScopes()
            ->where('hotel_id', $hotelId)
            ->where('legacy_customer_code', $legacyCustomer->legacy_customer_code)
            ->first();

        if (!$guest && $sync) {
            $guest = Guest::withoutGlobalScopes()
                ->where('hotel_id', $hotelId)
                ->where(function($q) use ($legacyCustomer) {
                    $hasCondition = false;
                    if ($legacyCustomer->id_number) {
                        $q->orWhere('id_number', $legacyCustomer->id_number);
                        $hasCondition = true;
                    }
                    if ($legacyCustomer->phone && $legacyCustomer->phone !== '-') {
                        $q->orWhere('phone', $legacyCustomer->phone);
                        $hasCondition = true;
                    }
                    if (!$hasCondition) {
                        $q->whereRaw('1=0');
                    }
                })
                ->first();
        }

        if ($guest) {
            $guest->fill(array_filter($attributes, fn($v) => $v !== null && $v !== ''));
            if ($guest->isDirty()) {
                $guest->save();
                $this->stats['guests_updated']++;
            }
            return;
        }

        Guest::create($attributes);
        $this->stats['guests_created']++;
    }

    protected function yieldCsv(string $path): \Generator
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Unable to open CSV: {$path}");
        }

        $headers = null;
        while (($row = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                $headers = array_map(fn($v) => trim((string)$v), $row);
                continue;
            }

            $data = [];
            foreach ($headers as $index => $header) {
                $data[$header] = isset($row[$index]) ? trim((string)$row[$index]) : null;
            }

            if (count($row) > count($headers)) {
                $data['_extra'] = array_slice($row, count($headers));
            }

            yield $data;
        }
        fclose($handle);
    }

    protected function normalizeString(mixed $value): ?string
    {
        if ($value === null) return null;
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }

    protected function normalizeInteger(mixed $value): ?int
    {
        $value = $this->normalizeString($value);
        if ($value === null || !is_numeric($value)) return null;
        return (int)$value;
    }

    protected function normalizeCitizenship(?string $value): ?string
    {
        $val = $this->normalizeString($value);
        if (!$val) return null;
        $upper = strtoupper($val);
        $upper = str_replace(' ', '', $upper);
        if (str_contains($upper, 'INDONESIA') || $upper === 'ID' || $upper === 'IDN' || $upper === 'WNI') return 'WNI';
        if ($upper === 'ASING' || $upper === 'FOREIGN' || $upper === 'WNA') return 'WNA';
        return $upper;
    }

    protected function mapNationality(?string $citizenshipCode): string
    {
        $code = strtoupper((string)$citizenshipCode);
        if ($code === 'WNI') return 'Indonesian';
        if ($code === 'WNA') return 'Foreigner';
        return 'Indonesian';
    }

    protected function printSummary(): void
    {
        $this->newLine();
        $this->info('Import Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Customer Types Imported', $this->stats['types_imported']],
                ['Staged (Total/Upserted)', $this->stats['staged_total']],
                ['Staged (Skipped/Missing Code)', $this->stats['staged_skipped']],
                ['Staged (Failed)', $this->stats['staged_failed']],
                ['Guests Created', $this->stats['guests_created']],
                ['Guests Updated', $this->stats['guests_updated']],
                ['Guests Failed', $this->stats['guests_failed']],
            ]
        );
    }
}
