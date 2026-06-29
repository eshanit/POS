<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class SaleController extends Controller
{
    public function create()
    {
        return Inertia::render('Sales/Create', [
            'products' => Product::orderBy('name')->get(['id', 'name', 'sku', 'price_usd', 'price_zig', 'current_quantity']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'customer_name' => 'nullable|string|max:255',
            'currency' => 'required|in:USD,ZIG',
            'payment_method' => 'required|in:ecocash,bank,cash',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, &$sale) {
            $totalAmount = 0;
            $saleItems = [];
            $requestedQuantities = collect($validated['items'])
                ->groupBy('product_id')
                ->map(fn ($items) => $items->sum('quantity'));

            $products = Product::whereIn('id', $requestedQuantities->keys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($requestedQuantities as $productId => $quantity) {
                $product = $products->get((int) $productId);

                if (! $product || $product->current_quantity < $quantity) {
                    $productName = $product?->name ?? 'selected product';
                    $availableQuantity = $product?->current_quantity ?? 0;

                    throw ValidationException::withMessages([
                        'items' => "Insufficient stock for {$productName}. Available: {$availableQuantity}",
                    ]);
                }
            }

            foreach ($validated['items'] as $item) {
                $product = $products->get((int) $item['product_id']);

                $lineTotal = $item['quantity'] * $item['unit_price'];
                $totalAmount += $lineTotal;

                $saleItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $lineTotal,
                ];
            }

            $sale = Sale::create([
                'user_id' => Auth::id(),
                'date' => $validated['date'],
                'customer_name' => $validated['customer_name'],
                'currency' => $validated['currency'],
                'payment_method' => $validated['payment_method'],
                'status' => 'completed',
                'total_amount' => $totalAmount,
            ]);

            foreach ($saleItems as $item) {
                $sale->items()->create($item);

                $product = $products->get((int) $item['product_id']);
                $product->current_quantity -= $item['quantity'];
                $product->save();

                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'user_id' => Auth::id(),
                    'type' => 'out',
                    'quantity' => $item['quantity'],
                    'reference' => "Sale #{$sale->id}",
                    'date' => $validated['date'],
                ]);
            }
        });

        return redirect()->route('sales.index')
            ->with('success', "Sale #{$sale->id} recorded successfully.");
    }

    public function index(Request $request)
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

        $sortBy = in_array($request->sort_by, ['date', 'customer_name', 'currency', 'payment_method', 'total_amount', 'status'])
            ? $request->sort_by : 'date';
        $sortDir = $request->sort_direction === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDir);

        return Inertia::render('Sales/Index', [
            'sales' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['date_from', 'date_to', 'currency', 'payment_method', 'sort_by', 'sort_direction']),
        ]);
    }

    public function show(Sale $sale)
    {
        return Inertia::render('Sales/Show', [
            'sale' => $sale->load(['user', 'items.product']),
        ]);
    }
}
