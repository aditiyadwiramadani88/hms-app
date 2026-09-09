<?php

namespace Database\Seeders;

use App\Models\Inventory;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Amenities
        Inventory::create([
            'name' => 'Toothbrush',
            'category' => 'amenity',
            'stock' => 150,
            'min_stock' => 30,
            'unit' => 'pcs',
            'price_per_unit' => 5000,
            'is_active' => true,
        ]);

        Inventory::create([
            'name' => 'Toothpaste',
            'category' => 'amenity',
            'stock' => 150,
            'min_stock' => 30,
            'unit' => 'pcs',
            'price_per_unit' => 8000,
            'is_active' => true,
        ]);

        Inventory::create([
            'name' => 'Soap',
            'category' => 'amenity',
            'stock' => 200,
            'min_stock' => 50,
            'unit' => 'pcs',
            'price_per_unit' => 7000,
            'is_active' => true,
        ]);

        Inventory::create([
            'name' => 'Shampoo',
            'category' => 'amenity',
            'stock' => 150,
            'min_stock' => 30,
            'unit' => 'pcs',
            'price_per_unit' => 10000,
            'is_active' => true,
        ]);

        Inventory::create([
            'name' => 'Towel',
            'category' => 'amenity',
            'stock' => 200,
            'min_stock' => 50,
            'unit' => 'pcs',
            'price_per_unit' => 20000,
            'is_active' => true,
        ]);

        // Food
        Inventory::create([
            'name' => 'Coffee',
            'category' => 'food',
            'stock' => 80,
            'min_stock' => 20,
            'unit' => 'pcs',
            'price_per_unit' => 10000,
            'is_active' => true,
        ]);

        Inventory::create([
            'name' => 'Tea',
            'category' => 'food',
            'stock' => 100,
            'min_stock' => 25,
            'unit' => 'pcs',
            'price_per_unit' => 10000,
            'is_active' => true,
        ]);

        Inventory::create([
            'name' => 'Bread',
            'category' => 'food',
            'stock' => 50,
            'min_stock' => 15,
            'unit' => 'pcs',
            'price_per_unit' => 15000,
            'is_active' => true,
        ]);

        Inventory::create([
            'name' => 'Egg',
            'category' => 'food',
            'stock' => 100,
            'min_stock' => 25,
            'unit' => 'pcs',
            'price_per_unit' => 25000,
            'is_active' => true,
        ]);

        // Beverage
        Inventory::create([
            'name' => 'Mineral Water',
            'category' => 'beverage',
            'stock' => 200,
            'min_stock' => 50,
            'unit' => 'bottle',
            'price_per_unit' => 5000,
            'is_active' => true,
        ]);

        Inventory::create([
            'name' => 'Juice',
            'category' => 'beverage',
            'stock' => 100,
            'min_stock' => 25,
            'unit' => 'bottle',
            'price_per_unit' => 12000,
            'is_active' => true,
        ]);

        Inventory::create([
            'name' => 'Soft Drink',
            'category' => 'beverage',
            'stock' => 150,
            'min_stock' => 40,
            'unit' => 'bottle',
            'price_per_unit' => 15000,
            'is_active' => true,
        ]);
    }
}
