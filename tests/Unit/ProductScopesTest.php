<?php

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('scopes inStock lowStock and outOfStock behave correctly', function () {
    // Create products with varying quantities and reorder levels
    Product::create([
        'name' => 'In Stock Item',
        'sku' => 'IN-001',
        'cost_price' => 10.00,
        'price_usd' => 15.00,
        'price_zig' => 150.00,
        'current_quantity' => 20,
        'reorder_level' => 5,
    ]);

    Product::create([
        'name' => 'Low Stock Item',
        'sku' => 'LOW-001',
        'cost_price' => 8.00,
        'price_usd' => 12.00,
        'price_zig' => 120.00,
        'current_quantity' => 3,
        'reorder_level' => 5,
    ]);

    Product::create([
        'name' => 'Out Of Stock Item',
        'sku' => 'OUT-001',
        'cost_price' => 5.00,
        'price_usd' => 9.00,
        'price_zig' => 90.00,
        'current_quantity' => 0,
        'reorder_level' => 5,
    ]);

    expect(Product::inStock()->count())->toBe(1);
    expect(Product::lowStock()->count())->toBe(2); // lowStock scope in model counts <= reorder_level (includes out of stock)
    expect(Product::outOfStock()->count())->toBe(1);
});
