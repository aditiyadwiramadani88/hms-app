<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $hotelId = \App\Models\Hotel::first()?->id;
        if (!$hotelId) return;

        $methods = [
            ['name' => 'Tunai', 'code' => 'cash', 'sort_order' => 1],
            ['name' => 'BCA Harian', 'code' => 'bank_transfer', 'sort_order' => 2],
            ['name' => 'Charge to Room', 'code' => 'charge_to_room', 'sort_order' => 3],
        ];

        foreach ($methods as $m) {
            PaymentMethod::firstOrCreate(
                ['hotel_id' => $hotelId, 'code' => $m['code']],
                ['name' => $m['name'], 'sort_order' => $m['sort_order'], 'is_active' => true]
            );
        }
    }
}
