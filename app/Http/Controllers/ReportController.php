<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        $query = Sale::with('user');

        if ($request->date_from) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('date', '<=', $request->date_to);
        }
        if ($request->currency) {
            $query->where('currency', $request->currency);
        }
        if ($request->payment_method) {
            $query->where('payment_method', $request->payment_method);
        }

        $sales = $query->orderByDesc('date')->paginate(20)->withQueryString();

        $totals = [
            'USD' => Sale::when($request->date_from, fn($q) => $q->whereDate('date', '>=', $request->date_from))
                ->when($request->date_to, fn($q) => $q->whereDate('date', '<=', $request->date_to))
                ->when($request->payment_method, fn($q) => $q->where('payment_method', $request->payment_method))
                ->where('currency', 'USD')
                ->sum('total_amount'),
            'ZIG' => Sale::when($request->date_from, fn($q) => $q->whereDate('date', '>=', $request->date_from))
                ->when($request->date_to, fn($q) => $q->whereDate('date', '<=', $request->date_to))
                ->when($request->payment_method, fn($q) => $q->where('payment_method', $request->payment_method))
                ->where('currency', 'ZIG')
                ->sum('total_amount'),
        ];

        return Inertia::render('Reports/Sales', [
            'sales' => $sales,
            'totals' => $totals,
            'filters' => $request->only(['date_from', 'date_to', 'currency', 'payment_method']),
        ]);
    }

    public function inventory(Request $request)
    {
        if (Gate::has('view-inventory-report')) {
            Gate::authorize('view-inventory-report');
        }

        $startDate = $request->start_date ?? now()->subDays(30)->format('Y-m-d');
        $endDate = $request->end_date ?? now()->format('Y-m-d');

        $query = Product::with('category', 'supplier');

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->stock_status) {
            if ($request->stock_status === 'in') {
                $query->whereColumn('current_quantity', '>', 'reorder_level');
            } elseif ($request->stock_status === 'low') {
                $query->whereColumn('current_quantity', '<=', 'reorder_level')
                      ->where('current_quantity', '>', 0);
            } elseif ($request->stock_status === 'out') {
                $query->where('current_quantity', '<=', 0);
            }
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('sku', 'like', "%{$request->search}%");
            });
        }

        $allowedSorts = [
            'name' => 'name',
            'sku' => 'sku',
            'current_quantity' => 'current_quantity',
            'reorder_level' => 'reorder_level',
            'cost_price' => 'cost_price',
            'price_usd' => 'price_usd',
            'price_zig' => 'price_zig',
            'total_cost' => DB::raw('current_quantity * cost_price'),
            'potential_rev_usd' => DB::raw('current_quantity * price_usd'),
            'potential_rev_zig' => DB::raw('current_quantity * price_zig'),
        ];

        $sortBy = $request->sort_by;
        $sortDir = strtolower($request->sort_dir ?? 'asc') === 'desc' ? 'desc' : 'asc';

        if ($sortBy && isset($allowedSorts[$sortBy])) {
            $order = $allowedSorts[$sortBy];
            if ($order instanceof \Illuminate\Database\Query\Expression) {
                $query->orderByRaw("({$order->getValue()}) $sortDir");
            } else {
                $query->orderBy($order, $sortDir);
            }
        } else {
            $query->orderBy('name');
        }

        $products = $query->paginate(25)->withQueryString();

        $agg = Product::query();
        if ($request->category_id) {
            $agg->where('category_id', $request->category_id);
        }
        if ($request->stock_status) {
            if ($request->stock_status === 'in') {
                $agg->whereColumn('current_quantity', '>', 'reorder_level');
            } elseif ($request->stock_status === 'low') {
                $agg->whereColumn('current_quantity', '<=', 'reorder_level')
                    ->where('current_quantity', '>', 0);
            } elseif ($request->stock_status === 'out') {
                $agg->where('current_quantity', '<=', 0);
            }
        }
        if ($request->search) {
            $agg->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('sku', 'like', "%{$request->search}%");
            });
        }

        $stats = [
            'total_products' => $agg->count(),
            'low_stock_count' => (clone $agg)->whereColumn('current_quantity', '<=', 'reorder_level')->where('current_quantity', '>', 0)->count(),
            'total_units' => (clone $agg)->sum('current_quantity'),
            'total_cost_value' => (clone $agg)->selectRaw('SUM(current_quantity * cost_price) as total')->first()->total ?? 0,
            'total_potential_rev_usd' => (clone $agg)->selectRaw('SUM(current_quantity * price_usd) as total')->first()->total ?? 0,
            'total_potential_rev_zig' => (clone $agg)->selectRaw('SUM(current_quantity * price_zig) as total')->first()->total ?? 0,
            'expiring_soon_count' => Product::whereNotNull('expiry_date')
                ->where('expiry_date', '>=', now())
                ->where('expiry_date', '<=', now()->addDays(30))
                ->where('current_quantity', '>', 0)
                ->count(),
        ];

        // Fastest movers (Top 5 by units sold in date range)
        $fastestMovers = SaleItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->whereHas('sale', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate]);
            })
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->with('product')
            ->get()
            ->map(fn ($item) => [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name ?? 'Deleted',
                'product_sku' => $item->product->sku ?? '',
                'total_sold' => (int) $item->total_sold,
            ]);

        // Slowest movers (Bottom 5 including zero-sale products)
        $allProductIds = Product::when($request->category_id, fn ($q) => $q->where('category_id', $request->category_id))
            ->pluck('id');
        $salesData = SaleItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->whereHas('sale', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate]);
            })
            ->whereIn('product_id', $allProductIds)
            ->groupBy('product_id')
            ->pluck('total_sold', 'product_id');

        $movementData = [];
        foreach ($allProductIds as $id) {
            $movementData[$id] = (int) ($salesData[$id] ?? 0);
        }
        asort($movementData);
        $bottomIds = array_keys(array_slice($movementData, 0, 5, true));

        $slowestProducts = Product::whereIn('id', $bottomIds)->get()->keyBy('id');
        $slowestMovers = [];
        foreach ($bottomIds as $id) {
            $p = $slowestProducts->get($id);
            if ($p) {
                $slowestMovers[] = [
                    'product_id' => $id,
                    'product_name' => $p->name,
                    'product_sku' => $p->sku,
                    'total_sold' => $movementData[$id],
                    'current_quantity' => $p->current_quantity,
                ];
            }
        }

        // Expiry alerts
        $expiringSoon = Product::whereNotNull('expiry_date')
            ->where('expiry_date', '>=', now())
            ->where('expiry_date', '<=', now()->addDays(30))
            ->where('current_quantity', '>', 0)
            ->orderBy('expiry_date')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'expiry_date' => $p->expiry_date->format('Y-m-d'),
                'days_until_expiry' => now()->diffInDays($p->expiry_date, false),
                'current_quantity' => $p->current_quantity,
                'potential_loss_usd' => $p->current_quantity * $p->cost_price,
            ]);

        return Inertia::render('Reports/Inventory', [
            'products' => $products,
            'stats' => $stats,
            'fastest_movers' => $fastestMovers,
            'slowest_movers' => $slowestMovers,
            'expiring_soon' => $expiringSoon,
            'filters' => $request->only(['start_date', 'end_date', 'category_id', 'stock_status', 'search', 'sort_by', 'sort_dir']),
            'categories' => \App\Models\Category::orderBy('name')->get(),
        ]);
    }

    public function exportInventory(Request $request)
    {
        if (Gate::has('view-inventory-report')) {
            Gate::authorize('view-inventory-report');
        }

        $query = Product::with('category', 'supplier');

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->stock_status) {
            if ($request->stock_status === 'in') {
                $query->whereColumn('current_quantity', '>', 'reorder_level');
            } elseif ($request->stock_status === 'low') {
                $query->whereColumn('current_quantity', '<=', 'reorder_level')
                      ->where('current_quantity', '>', 0);
            } elseif ($request->stock_status === 'out') {
                $query->where('current_quantity', '<=', 0);
            }
        } elseif ($request->low_stock_only) {
            $query->whereColumn('current_quantity', '<=', 'reorder_level');
        }
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('sku', 'like', "%{$request->search}%");
            });
        }

        $filename = 'inventory_export_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['SKU','Product','Category','Qty','Cost Price','Total Cost','Price USD','Price ZIG','Potential Rev USD','Potential Rev ZIG','Status']);

            foreach ($query->cursor() as $p) {
                $totalCost = $p->current_quantity * $p->cost_price;
                $revUsd = $p->current_quantity * $p->price_usd;
                $revZig = $p->current_quantity * $p->price_zig;
                $status = $p->current_quantity <= 0 ? 'Out of Stock' : ($p->current_quantity <= $p->reorder_level ? 'Low Stock' : 'In Stock');

                fputcsv($handle, [
                    $p->sku,
                    $p->name,
                    $p->category->name ?? 'Uncategorized',
                    $p->current_quantity,
                    number_format($p->cost_price, 2, '.', ''),
                    number_format($totalCost, 2, '.', ''),
                    number_format($p->price_usd, 2, '.', ''),
                    number_format($p->price_zig, 2, '.', ''),
                    number_format($revUsd, 2, '.', ''),
                    number_format($revZig, 2, '.', ''),
                    $status,
                ]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    public function supplier(Request $request)
    {
        $suppliers = Supplier::withCount('products')
            ->get()
            ->map(function ($supplier) {
                $sales = DB::table('sale_items')
                    ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                    ->join('products', 'sale_items.product_id', '=', 'products.id')
                    ->where('products.supplier_id', $supplier->id)
                    ->selectRaw('sales.currency, SUM(sale_items.total) as total')
                    ->groupBy('sales.currency')
                    ->pluck('total', 'currency');

                $totalStock = Product::where('supplier_id', $supplier->id)->sum('current_quantity');

                return [
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                    'contact_person' => $supplier->contact_person,
                    'phone' => $supplier->phone,
                    'email' => $supplier->email,
                    'total_products' => $supplier->products_count,
                    'total_stock' => $totalStock,
                    'sales_usd' => $sales['USD'] ?? 0,
                    'sales_zig' => $sales['ZIG'] ?? 0,
                    'product_count' => $supplier->products_count,
                ];
            });

        if ($request->sort_by === 'sales') {
            $suppliers = $suppliers->sortByDesc(fn($s) => $s['sales_usd'] + $s['sales_zig']);
        } elseif ($request->sort_by === 'stock') {
            $suppliers = $suppliers->sortByDesc('total_stock');
        } else {
            $suppliers = $suppliers->sortBy('name');
        }

        return Inertia::render('Reports/Supplier', [
            'suppliers' => $suppliers->values(),
            'filters' => $request->only(['sort_by']),
        ]);
    }

    public function shrinkage(Request $request)
    {
        if (\Illuminate\Support\Facades\Gate::has('view-shrinkage-report')) {
            \Illuminate\Support\Facades\Gate::authorize('view-shrinkage-report');
        }
        $query = \App\Models\StockMovement::query()->where('type', 'out');

        if ($request->date_from) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $groups = $query->selectRaw('reason, SUM(quantity) as total_qty, SUM(quantity * unit_cost_usd) as total_loss')
            ->groupBy('reason')
            ->get()
            ->map(function ($r) {
                return [
                    'reason' => $r->reason,
                    'total_qty' => (int) $r->total_qty,
                    'total_loss' => (float) $r->total_loss,
                ];
            });

        return Inertia::render('Reports/Shrinkage', [
            'groups' => $groups,
            'filters' => $request->only(['date_from', 'date_to']),
        ]);
    }

    public function exportShrinkage(Request $request)
    {
        if (\Illuminate\Support\Facades\Gate::has('view-shrinkage-report')) {
            \Illuminate\Support\Facades\Gate::authorize('view-shrinkage-report');
        }

        $query = \App\Models\StockMovement::query()->where('type', 'out');

        if ($request->date_from) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $groups = $query->selectRaw('reason, SUM(quantity) as total_qty, SUM(quantity * unit_cost_usd) as total_loss')
            ->groupBy('reason')
            ->get();

        $filename = 'shrinkage_report_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($groups) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Reason','Total Quantity','Total Loss (USD)']);
            foreach ($groups as $g) {
                fputcsv($handle, [$g->reason, $g->total_qty, number_format($g->total_loss, 2, '.', '')]);
            }
            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }
}
