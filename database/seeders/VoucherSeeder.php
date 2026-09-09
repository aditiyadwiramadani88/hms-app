<?php

namespace Database\Seeders;

use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class VoucherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Voucher::firstOrCreate(['code' => 'WELCOME10'], [
            'name' => 'Welcome Discount',
            'description' => 'Get 10% discount on your booking',
            'type' => 'percentage',
            'value' => 10,
            'min_booking_amount' => 500000,
            'max_discount' => 100000,
            'usage_limit' => 100,
            'usage_count' => 0,
            'valid_from' => Carbon::today(),
            'valid_until' => Carbon::today()->addYear(),
            'is_active' => true,
        ]);

        Voucher::firstOrCreate(['code' => 'HEMAT50K'], [
            'name' => 'Hemat 50K',
            'description' => 'Get 50K discount on your booking',
            'type' => 'fixed',
            'value' => 50000,
            'min_booking_amount' => 300000,
            'max_discount' => null,
            'usage_limit' => 50,
            'usage_count' => 0,
            'valid_from' => Carbon::today(),
            'valid_until' => Carbon::today()->addMonths(6),
            'is_active' => true,
        ]);
    }
}
