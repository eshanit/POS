<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Beverages',
            'Food & Snacks',
            'Cleaning Supplies',
            'Personal Care',
            'Stationery',
            'Electronics',
            'Clothing',
            'Hardware',
        ];

        foreach ($categories as $name) {
            Category::create(compact('name'));
        }
    }
}
