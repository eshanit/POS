<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $todaySales = Sale::whereDate('date', today())->get();
        $lowStockProducts = Product::lowStock()->with('category')->limit(10)->get();

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_products' => Product::count(),
                'low_stock_count' => Product::lowStock()->count(),
                'today_sales_usd' => $todaySales->where('currency', 'USD')->sum('total_amount'),
                'today_sales_zig' => $todaySales->where('currency', 'ZIG')->sum('total_amount'),
            ],
            'low_stock_products' => $lowStockProducts,
        ]);
    }
}
