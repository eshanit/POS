<?php

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('deducts stock successfully and records movement', function () {
    $p = Product::create([
        'name' => 'Deduct Item',
        'sku' => 'DED-001',
        'cost_price' => 3.50,
        'price_usd' => 5.00,
        'price_zig' => 50.00,
        'current_quantity' => 10,
        'reorder_level' => 2,
    ]);

    $response = $this->post('/stock/deduct', [
        'product_id' => $p->id,
        'reason' => 'breakage',
        'quantity' => 4,
        'date' => now()->toDateString(),
        'notes' => 'Broken in storage',
    ]);

    $response->assertRedirect('/stock/deduct');

    $p->refresh();
    expect($p->current_quantity)->toBe(6);

    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $p->id,
        'type' => 'out',
        'reason' => 'breakage',
        'quantity' => 4,
    ]);
});

it('prevents deduction when insufficient stock', function () {
    $p = Product::create([
        'name' => 'Low Item',
        'sku' => 'LOW-002',
        'cost_price' => 2.00,
        'price_usd' => 3.00,
        'price_zig' => 30.00,
        'current_quantity' => 1,
        'reorder_level' => 2,
    ]);

    $response = $this->post('/stock/deduct', [
        'product_id' => $p->id,
        'reason' => 'sale',
        'quantity' => 5,
        'date' => now()->toDateString(),
        'reference' => 'SLIP-123',
    ]);

    $response->assertSessionHasErrors('quantity');

    $p->refresh();
    expect($p->current_quantity)->toBe(1);
});

it('requires reference for sale deductions', function () {
    $p = Product::create([
        'name' => 'Sale Item',
        'sku' => 'SALE-003',
        'cost_price' => 1.00,
        'price_usd' => 2.00,
        'price_zig' => 20.00,
        'current_quantity' => 5,
        'reorder_level' => 1,
    ]);

    $response = $this->post('/stock/deduct', [
        'product_id' => $p->id,
        'reason' => 'sale',
        'quantity' => 2,
        'date' => now()->toDateString(),
    ]);

    $response->assertSessionHasErrors('reference');
});
