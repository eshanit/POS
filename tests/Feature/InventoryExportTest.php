<?php

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('exports inventory as csv', function () {
    Product::create([
        'name' => 'CSV Item',
        'sku' => 'CSV-001',
        'cost_price' => 2.50,
        'price_usd' => 5.00,
        'price_zig' => 50.00,
        'current_quantity' => 10,
        'reorder_level' => 2,
    ]);

    $response = $this->get('/reports/inventory/export');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/csv');

    $content = $response->getContent();
    // Basic CSV checks
    expect(str_contains($content, 'SKU,Product,Category,Qty,Cost Price,Total Cost'))->toBeTrue();
    expect(str_contains($content, 'CSV-001'))->toBeTrue();
});
