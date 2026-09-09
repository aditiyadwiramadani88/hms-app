<?php

namespace Database\Seeders;

use App\Models\BookingSource;
use App\Models\Hotel;
use Illuminate\Database\Seeder;

class BookingSourceSeeder extends Seeder
{
    public function run(): void
    {
        $hotels = Hotel::all();

        $sources = [
            ['name' => 'Langsung / Walk-in', 'color' => 'secondary'],
            ['name' => 'WhatsApp',            'color' => 'success'],
            ['name' => 'Traveloka',           'color' => 'primary'],
            ['name' => 'Agoda',               'color' => 'danger'],
            ['name' => 'Booking.com',         'color' => 'info'],
            ['name' => 'Tiket.com',           'color' => 'warning'],
            ['name' => 'Airbnb',              'color' => 'danger'],
            ['name' => 'Instagram',           'color' => 'warning'],
        ];

        foreach ($hotels as $hotel) {
            foreach ($sources as $source) {
                BookingSource::firstOrCreate(
                    ['hotel_id' => $hotel->id, 'name' => $source['name']],
                    ['color' => $source['color'], 'is_active' => true]
                );
            }
        }
    }
}
