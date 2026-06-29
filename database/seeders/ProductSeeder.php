<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'Coca-Cola 2L', 'sku' => 'BEV001', 'category_id' => 1, 'supplier_id' => 1, 'cost_price' => 1.20, 'price_usd' => 2.00, 'price_zig' => 250.00, 'reorder_level' => 10],
            ['name' => 'Spring Water 500ml', 'sku' => 'BEV002', 'category_id' => 1, 'supplier_id' => 1, 'cost_price' => 0.50, 'price_usd' => 1.00, 'price_zig' => 120.00, 'reorder_level' => 20],
            ['name' => 'Orange Juice 1L', 'sku' => 'BEV003', 'category_id' => 1, 'supplier_id' => 2, 'cost_price' => 1.80, 'price_usd' => 3.00, 'price_zig' => 380.00, 'reorder_level' => 10],
            ['name' => 'Potato Chips 150g', 'sku' => 'FOD001', 'category_id' => 2, 'supplier_id' => 2, 'cost_price' => 0.90, 'price_usd' => 1.50, 'price_zig' => 190.00, 'reorder_level' => 15],
            ['name' => 'Chocolate Bar', 'sku' => 'FOD002', 'category_id' => 2, 'supplier_id' => 3, 'cost_price' => 1.10, 'price_usd' => 2.00, 'price_zig' => 250.00, 'reorder_level' => 20],
            ['name' => 'Bread Loaf', 'sku' => 'FOD003', 'category_id' => 2, 'supplier_id' => 2, 'cost_price' => 0.70, 'price_usd' => 1.20, 'price_zig' => 150.00, 'reorder_level' => 10],
            ['name' => 'All-Purpose Cleaner 500ml', 'sku' => 'CLN001', 'category_id' => 3, 'supplier_id' => 4, 'cost_price' => 1.50, 'price_usd' => 2.50, 'price_zig' => 310.00, 'reorder_level' => 10],
            ['name' => 'Dish Soap 750ml', 'sku' => 'CLN002', 'category_id' => 3, 'supplier_id' => 4, 'cost_price' => 1.20, 'price_usd' => 2.00, 'price_zig' => 250.00, 'reorder_level' => 12],
            ['name' => 'Toilet Cleaner', 'sku' => 'CLN003', 'category_id' => 3, 'supplier_id' => 4, 'cost_price' => 1.00, 'price_usd' => 1.80, 'price_zig' => 220.00, 'reorder_level' => 8],
            ['name' => 'Shampoo 250ml', 'sku' => 'PRS001', 'category_id' => 4, 'supplier_id' => 5, 'cost_price' => 2.50, 'price_usd' => 4.00, 'price_zig' => 500.00, 'reorder_level' => 8],
            ['name' => 'Toothpaste 100ml', 'sku' => 'PRS002', 'category_id' => 4, 'supplier_id' => 5, 'cost_price' => 1.20, 'price_usd' => 2.00, 'price_zig' => 250.00, 'reorder_level' => 15],
            ['name' => 'Soap Bar', 'sku' => 'PRS003', 'category_id' => 4, 'supplier_id' => 5, 'cost_price' => 0.60, 'price_usd' => 1.00, 'price_zig' => 130.00, 'reorder_level' => 20],
            ['name' => 'Deodorant 50ml', 'sku' => 'PRS004', 'category_id' => 4, 'supplier_id' => 5, 'cost_price' => 2.00, 'price_usd' => 3.50, 'price_zig' => 440.00, 'reorder_level' => 8],
            ['name' => 'Notebook A5', 'sku' => 'STN001', 'category_id' => 5, 'supplier_id' => 3, 'cost_price' => 0.50, 'price_usd' => 1.00, 'price_zig' => 120.00, 'reorder_level' => 20],
            ['name' => 'Ballpoint Pen (Pack 10)', 'sku' => 'STN002', 'category_id' => 5, 'supplier_id' => 3, 'cost_price' => 0.80, 'price_usd' => 1.50, 'price_zig' => 190.00, 'reorder_level' => 15],
            ['name' => 'USB Cable 1m', 'sku' => 'ELC001', 'category_id' => 6, 'supplier_id' => 5, 'cost_price' => 1.00, 'price_usd' => 2.00, 'price_zig' => 250.00, 'reorder_level' => 10],
            ['name' => 'Phone Charger', 'sku' => 'ELC002', 'category_id' => 6, 'supplier_id' => 5, 'cost_price' => 3.00, 'price_usd' => 5.00, 'price_zig' => 630.00, 'reorder_level' => 5],
            ['name' => 'AA Batteries (Pack 4)', 'sku' => 'ELC003', 'category_id' => 6, 'supplier_id' => 4, 'cost_price' => 1.50, 'price_usd' => 2.50, 'price_zig' => 310.00, 'reorder_level' => 15],
            ['name' => 'Light Bulb LED', 'sku' => 'ELC004', 'category_id' => 6, 'supplier_id' => 4, 'cost_price' => 1.80, 'price_usd' => 3.00, 'price_zig' => 380.00, 'reorder_level' => 10],
            ['name' => 'T-Shirt (Cotton)', 'sku' => 'CLO001', 'category_id' => 7, 'supplier_id' => 1, 'cost_price' => 4.00, 'price_usd' => 8.00, 'price_zig' => 1000.00, 'reorder_level' => 5],
            ['name' => 'Socks (Pair)', 'sku' => 'CLO002', 'category_id' => 7, 'supplier_id' => 1, 'cost_price' => 1.50, 'price_usd' => 3.00, 'price_zig' => 380.00, 'reorder_level' => 10],
            ['name' => 'Nails 1kg', 'sku' => 'HRD001', 'category_id' => 8, 'supplier_id' => 4, 'cost_price' => 2.00, 'price_usd' => 3.50, 'price_zig' => 440.00, 'reorder_level' => 8],
            ['name' => 'Paint Brush 2"', 'sku' => 'HRD002', 'category_id' => 8, 'supplier_id' => 4, 'cost_price' => 1.20, 'price_usd' => 2.00, 'price_zig' => 250.00, 'reorder_level' => 10],
            ['name' => 'Masking Tape', 'sku' => 'HRD003', 'category_id' => 8, 'supplier_id' => 4, 'cost_price' => 0.80, 'price_usd' => 1.50, 'price_zig' => 190.00, 'reorder_level' => 12],
            ['name' => 'Sandpaper (Pack 5)', 'sku' => 'HRD004', 'category_id' => 8, 'supplier_id' => 4, 'cost_price' => 1.00, 'price_usd' => 1.80, 'price_zig' => 220.00, 'reorder_level' => 10],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
