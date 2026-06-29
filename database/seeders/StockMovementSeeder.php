<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Seeder;

class StockMovementSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all();

        foreach ($products as $product) {
            $qty = rand(20, 100);

            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => 1,
                'type' => 'in',
                'quantity' => $qty,
                'unit_cost_usd' => $product->cost_price,
                'reference' => 'Initial stock',
                'date' => now()->subDays(rand(1, 30)),
            ]);

            $product->current_quantity = $qty;
            $product->save();
        }
    }
}
