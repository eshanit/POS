<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class StockController extends Controller
{
    public function receiveForm()
    {
        return Inertia::render('Stock/Receive', [
            'products' => Product::orderBy('name')->get(['id', 'name', 'sku', 'current_quantity']),
        ]);
    }

    public function storeReceive(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'unit_cost_usd' => 'required|numeric|min:0',
            'date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {
            $product = Product::whereKey($validated['product_id'])->lockForUpdate()->firstOrFail();

            StockMovement::create([
                'product_id' => $validated['product_id'],
                'user_id' => Auth::id(),
                'type' => 'in',
                'quantity' => $validated['quantity'],
                'unit_cost_usd' => $validated['unit_cost_usd'],
                'reference' => $validated['reference'],
                'date' => $validated['date'],
                'notes' => $validated['notes'],
            ]);

            $product->current_quantity += $validated['quantity'];
            $product->save();
        });

        return redirect()->route('stock.receive')
            ->with('success', 'Stock received successfully.');
    }

    public function adjustForm()
    {
        return Inertia::render('Stock/Adjust', [
            'products' => Product::orderBy('name')->get(['id', 'name', 'sku', 'current_quantity']),
        ]);
    }

    public function deductForm()
    {
        $recent = StockMovement::with('product', 'user')
            ->where('type', 'out')
            ->orderByDesc('date')
            ->limit(20)
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'date' => $m->date->toDateString(),
                    'product' => $m->product ? ['id' => $m->product->id, 'name' => $m->product->name, 'sku' => $m->product->sku] : null,
                    'reason' => $m->reason,
                    'quantity' => $m->quantity,
                    'unit_cost_usd' => $m->unit_cost_usd,
                    'reference' => $m->reference,
                    'notes' => $m->notes,
                    'user' => $m->user ? ['id' => $m->user->id, 'name' => $m->user->name] : null,
                ];
            });

        return Inertia::render('Stock/Deduct', [
            'products' => Product::where('current_quantity', '>', 0)->orderBy('name')->get(['id', 'name', 'sku', 'current_quantity', 'cost_price']),
            'recent' => $recent,
        ]);
    }

    public function storeDeduct(Request $request)
    {
        if (\Illuminate\Support\Facades\Gate::has('manage-stock')) {
            \Illuminate\Support\Facades\Gate::authorize('manage-stock');
        }
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'reason' => 'required|in:sale,breakage,expired,return_supplier,adjustment',
            'quantity' => 'required|integer|min:1',
            'date' => 'required|date',
            'reference' => 'required_if:reason,sale|nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {
            $product = Product::whereKey($validated['product_id'])->lockForUpdate()->firstOrFail();

            if ($validated['quantity'] > $product->current_quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Insufficient stock. Available: {$product->current_quantity}",
                ]);
            }

            StockMovement::create([
                'product_id' => $validated['product_id'],
                'user_id' => Auth::id(),
                'type' => 'out',
                'reason' => $validated['reason'],
                'quantity' => $validated['quantity'],
                'unit_cost_usd' => $product->cost_price,
                'reference' => $validated['reference'] ?? null,
                'date' => $validated['date'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $product->current_quantity -= $validated['quantity'];
            $product->save();
        });

        return redirect()->route('stock.deduct')
            ->with('success', 'Stock deducted successfully.');
    }

    public function storeAdjustment(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer', // positive = add, negative = remove
            'date' => 'required|date',
            'notes' => 'required|string',
        ]);

        DB::transaction(function () use ($validated) {
            $product = Product::whereKey($validated['product_id'])->lockForUpdate()->firstOrFail();
            $newQuantity = $product->current_quantity + $validated['quantity'];

            if ($newQuantity < 0) {
                throw ValidationException::withMessages([
                    'quantity' => "Adjustment would make stock negative. Available: {$product->current_quantity}",
                ]);
            }

            StockMovement::create([
                'product_id' => $validated['product_id'],
                'user_id' => Auth::id(),
                'type' => 'adjustment',
                'quantity' => $validated['quantity'],
                'reference' => 'adjustment',
                'date' => $validated['date'],
                'notes' => $validated['notes'],
            ]);

            $product->current_quantity = $newQuantity;
            $product->save();
        });

        return redirect()->route('stock.adjust')
            ->with('success', 'Stock adjusted successfully.');
    }
}
