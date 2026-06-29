<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Illuminate\Database\Seeder;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all();
        $currencies = ['USD', 'ZIG'];
        $paymentMethods = ['ecocash', 'bank', 'cash'];

        for ($i = 0; $i < 20; $i++) {
            $currency = $currencies[array_rand($currencies)];
            $paymentMethod = $paymentMethods[array_rand($paymentMethods)];
            $date = now()->subDays(rand(0, 30));
            $totalAmount = 0;
            $items = [];

            $numItems = rand(1, 4);
            $selectedProducts = $products->random($numItems);

            foreach ($selectedProducts as $product) {
                $qty = rand(1, 3);
                $unitPrice = $currency === 'USD' ? $product->price_usd : $product->price_zig;
                $total = $qty * $unitPrice;
                $totalAmount += $total;

                $items[] = [
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'total' => $total,
                ];
            }

            $sale = Sale::create([
                'user_id' => 1,
                'date' => $date,
                'customer_name' => rand(0, 1) ? fake()->name() : null,
                'currency' => $currency,
                'payment_method' => $paymentMethod,
                'status' => 'completed',
                'total_amount' => $totalAmount,
            ]);

            foreach ($items as $item) {
                $sale->items()->create($item);

                if ($i < 15) {
                    $product = Product::find($item['product_id']);
                    if ($product) {
                        $product->current_quantity -= $item['quantity'];
                        if ($product->current_quantity < 0) {
                            $product->current_quantity = 0;
                        }
                        $product->save();

                        StockMovement::create([
                            'product_id' => $item['product_id'],
                            'user_id' => 1,
                            'type' => 'out',
                            'quantity' => $item['quantity'],
                            'reference' => "Sale #{$sale->id}",
                            'date' => $date,
                        ]);
                    }
                }
            }
        }
    }
}
