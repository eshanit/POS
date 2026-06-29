<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'sku', 'description', 'category_id', 'supplier_id',
        'cost_price', 'price_usd', 'price_zig',
        'current_quantity', 'reorder_level', 'expiry_date'
    ];

    protected function casts(): array
    {
        return [
            'deleted_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('current_quantity', '<=', 'reorder_level');
    }

    public function scopeInStock($query)
    {
        return $query->whereColumn('current_quantity', '>', 'reorder_level');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('current_quantity', '<=', 0);
    }
}