Perfect timing! Since you don't have a POS for real-time deductions, this **Manual Stock Deduction / Adjustment** page becomes the **bridge** between his physical paper records and the digital system. 

This page will handle every scenario where stock *leaves* the inventory without a formal POS sale. I've designed it to be highly flexible, with clear reasons for deduction so the client always knows *why* stock is disappearing.

Here is your dedicated **`stock_deduction_adjustments.md`** file. Feed this to DeepSeek Flash alongside the others.

---

```markdown
# Feature Specification: Manual Stock Deduction & Adjustments

**Parent Plan:** `plan.md`  
**Context:** The client does not have a POS system yet. Stock deductions (due to sales, breakages, returns, etc.) are recorded manually based on paper slips from the teller or physical stock takes.  
**Goal:** Provide a dedicated page to log **all stock-out events** that are not automated by a POS, ensuring the system's `current_quantity` always matches physical reality.

---

## 1. Core Business Concept: The "Deduction Reason"
To make reporting useful (and to replace his manual book), every deduction **must** have a specific reason. 

We will add a new `reason` field to the `stock_movements` table (type `out`). The allowed reasons are:

| Reason Code | Display Name | Description / Use Case |
| :--- | :--- | :--- |
| `sale` | **Sales Deduction** | *The primary use case.* Teller sends a paper sales report; owner manually deducts sold quantities. |
| `breakage` | **Breakage / Damage** | Items broken in the shop (e.g., glass bottles, crushed packets). |
| `expired` | **Expired Stock** | Products that have passed their expiry date and must be written off. |
| `return_supplier` | **Return to Supplier** | Defective or unsold goods sent back to the supplier. |
| `adjustment` | **Stock Take Correction** | Physical count differs from system count (e.g., theft, counting errors, or general shrinkage). |

---

## 2. UI/UX Page Layout (Shadcn)

### 2.1 Page Header
- Title: **"Deduct / Adjust Stock"**
- Subtitle: "Record stock leaving the inventory (sales, breakages, returns, etc.)"

### 2.2 The Deduction Form
Use a shadcn `Card` to contain the form. Inputs should be laid out in a clean grid.

| Field | Component | Validation / Behavior |
| :--- | :--- | :--- |
| **Date** | `Input` (type="date") | Required. Defaults to `today()`. |
| **Product** | `Select` (or Combobox) | Required. Searchable dropdown fetching products via API/Inertia. Only show products with `current_quantity > 0`. |
| **Reason** | `Select` | Required. Options: Sales Deduction, Breakage, Expired, Return to Supplier, Stock Take Correction. |
| **Quantity** | `Input` (type="number") | Required. Must be `> 0` and `<= product.current_quantity`. |
| **Unit Cost (USD)** | `Input` (disabled/read-only) | Auto-fills with `product.cost_price` for reference (history tracking). |
| **Reference / Invoice #** | `Input` (text) | Optional. E.g., Teller's paper slip number, Supplier RMA number. |
| **Notes** | `Textarea` | Optional. For extra context (e.g., "Expired batch #203"). |

- **Submit Button**: `Button` variant="destructive" (to signify stock is leaving) – "Deduct Stock".

### 2.3 Quick Action: "Deduct Sales from Paper"
Since he gets paper sales reports, consider adding a **"Bulk Deduct"** toggle or a separate tab. 
- For MVP, we'll keep it single-product per submission (it's safer and mimics his manual book entry style). 
- However, the dropdown should be fast to use so he can quickly enter 10 products one after another.

---

## 3. Database Migration Update

**Modify the existing `stock_movements` migration** (create a new migration to add the column):

```php
Schema::table('stock_movements', function (Blueprint $table) {
    $table->enum('reason', ['sale', 'breakage', 'expired', 'return_supplier', 'adjustment'])
          ->after('type')
          ->nullable(); // Nullable for 'in' movements, but required for 'out' (we'll enforce in code)
});
```

> **Note for AI**: Update the `$fillable` array in the `StockMovement` Model to include `'reason'`.

---

## 4. Backend Logic (Controller)

### 4.1 Route
- **URL**: `/stock/deduct`
- **Method**: GET (show form) & POST (store).
- **Controller**: `StockController@deductForm` and `StockController@storeDeduct`.

### 4.2 Validation Rules
```php
$request->validate([
    'date' => 'required|date',
    'product_id' => 'required|exists:products,id',
    'reason' => 'required|in:sale,breakage,expired,return_supplier,adjustment',
    'quantity' => 'required|integer|min:1',
    'reference' => 'nullable|string|max:255',
    'notes' => 'nullable|string',
]);
```

### 4.3 Critical Business Logic (Transaction)
1. Fetch the `Product`.
2. **Check Stock**: Ensure `$product->current_quantity >= $request->quantity`. If not, return an error: *"Insufficient stock. Available: X, Requested: Y"*.
3. **Create `StockMovement`**:
   - `type` = 'out'
   - `reason` = (selected reason)
   - `quantity` = requested quantity
   - `unit_cost_usd` = `$product->cost_price` (snapshot at time of deduction).
4. **Update `Product`**: `$product->current_quantity -= $request->quantity; $product->save();`
5. Wrap everything in `DB::transaction()`.

---

## 5. Why "Unit Cost" Matters here (Accounting)
Even though we are *deducting* stock, we store the `unit_cost_usd` at the time of deduction. 
- **For `sale` deductions**: This allows future profit calculations (COGS - Cost of Goods Sold).
- **For `breakage/expired`**: This allows the client to see **how much money they lost** in wastage (e.g., "I threw away $500 worth of expired milk this month").

---

## 6. Accompanying Report: "Shrinkage & Wastage Report" (Optional but highly recommended)
To give the client full value, add a small section to his Reports page that aggregates these deductions.

- **Filter**: Date range.
- **Group by**: Reason.
- **Show**: Total Quantity deducted per reason, and **Total Monetary Loss (USD)** (`sum(quantity * unit_cost_usd)`).
- **Example Output**:
  - **Breakage**: 15 units ($45.00 lost)
  - **Expired**: 50 units ($250.00 lost)
  - **Returns**: 5 units ($20.00 lost)
  - **Adjustments**: 3 units ($12.00 lost)

This report justifies the existence of this feature—it turns a manual chore into actionable business intelligence (e.g., "Stop ordering so much milk if 30% is expiring").

---

## 7. Frontend UX Enhancements (Vue)

### 7.1 Real-time Validation Feedback
- When a product is selected, show a Chip/Badge displaying: **"Available Qty: X"** right next to the quantity input.
- If the user types a quantity higher than available, disable the Submit button and show an error message directly under the input (using shadcn `Form` validation).

### 7.2 Success/Failure Flow
- On success: Redirect back to the Deduct Stock page with a `success` flash message (e.g., "15 units of 'Coca-Cola' deducted successfully.").
- Optionally, redirect to the Product Index or Inventory Report page to see the updated quantities immediately.

### 7.3 History Tab (Deduct Log)
At the bottom of the Deduct Stock page, add a **"Recent Deductions"** table (paginated) showing:
- Date | Product | Reason | Qty | Reference | User (if using multi-user later).
This gives the client immediate visibility into what was just entered.

---

## 8. Step-by-Step Development Checklist (for AI)

- [ ] Create a new migration to add `reason` enum to `stock_movements` table.
- [ ] Run `php artisan migrate` to update the schema.
- [ ] Update the `StockMovement` Model with `reason` in `$fillable`.
- [ ] Create the Vue page: `resources/js/Pages/Stock/Deduct.vue`.
- [ ] Create/Update `StockController` with `deductForm()` and `storeDeduct()` methods.
- [ ] Add the route: `Route::get('/stock/deduct', [StockController::class, 'deductForm'])->name('stock.deduct');` and POST route.
- [ ] In the Vue page, import Shadcn components: `Card`, `Select`, `Input`, `Textarea`, `Button`, `Form`, `Badge`.
- [ ] Implement the `product` selection dropdown (fetch products via `router.get` or a separate API endpoint `api/products?in_stock=true`).
- [ ] Implement the validation (frontend `useForm` with `quantity` max validation).
- [ ] Implement the backend transaction logic (with stock availability check).
- [ ] *(Optional Bonus)*: Build the "Shrinkage Report" in `ReportController` and link it in the Reports page.

---

## 9. Important Business Rule (Security)
Since this is a manual system, *trust* is involved. 
- **Best Practice**: Always require the user to enter a `Reference` (e.g., the teller's paper receipt number) for `sale` deductions. This allows the owner to cross-check the digital entry against the physical paper if a dispute arises.
- Validation rule: `reference` is `required_if:reason,sale`.

---

**This `stock_deduction_adjustments.md` ensures the system remains accurate even without a POS. It effectively replaces his manual book's "Stock Out" column with a digital, auditable trail.**