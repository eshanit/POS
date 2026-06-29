Below is a **comprehensive `plan.md`** you can feed directly to DeepSeek Flash (or any AI) to build your Laravel + Inertia + Vue + Shadcn inventory app.  
It covers the complete architecture, database schema, backend logic, and frontend UI — including all currency and payment handling.

---

```markdown
# Plan: Laravel + Inertia + Vue + Shadcn – Inventory Management with Dual Currency (USD/ZIG)

## 1. Project Overview
A simple inventory management system for a small shop.  
**Features**:
- Product management (CRUD) with **cost in USD**, **selling price in USD and ZIG**.
- Stock receiving (add quantities, cost in USD).
- Sales recording (multi‑product, select currency & payment method).
- Dashboard with low‑stock alerts and daily sales summaries (USD/ZIG separately).
- Reports (filter by date, currency, payment method).
- Single admin user (Laravel Breeze authentication).

---

## 2. Tech Stack
- **Backend**: Laravel 11.x (or latest stable)
- **Frontend**: Vue 3 (Composition API) + Inertia.js
- **UI Library**: Tailwind CSS + shadcn-vue (Radix Vue primitives)
- **Database**: MySQL / MariaDB
- **Auth**: Laravel Breeze (Inertia‑Vue stack)
- **HTTP Client**: Inertia forms (`useForm`)

---

## 3. Setup Steps (AI should execute in order)
## 3.1 - 3.5

- already done skip to 3.6

### 3.6 Set up Inertia routes
- All pages will be served via Inertia. Laravel routes will return `Inertia::render('PageName')`.
- We will create Vue pages inside `resources/js/Pages/`.

---

## 4. Database Schema (Migrations)

Create the following migrations. Use foreign key constraints and proper indexes.

### 4.1 Categories Table
```php
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique();
    $table->timestamps();
});
```

### 4.2 Suppliers Table
```php
Schema::create('suppliers', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('contact_person')->nullable();
    $table->string('phone')->nullable();
    $table->string('email')->nullable();
    $table->text('address')->nullable();
    $table->timestamps();
});
```

### 4.3 Products Table
```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('sku')->unique();
    $table->text('description')->nullable();
    $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
    $table->decimal('cost_price', 10, 2);                // always in USD
    $table->decimal('price_usd', 10, 2);                 // selling price in USD
    $table->decimal('price_zig', 10, 2);                 // selling price in ZIG
    $table->integer('current_quantity')->default(0);
    $table->integer('reorder_level')->default(5);
    $table->timestamps();
    $table->softDeletes();                                // for archiving products
});
```

### 4.4 Stock Movements Table
We track every change in quantity (stock‑in / stock‑out / adjustment).
```php
Schema::create('stock_movements', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // who recorded it
    $table->enum('type', ['in', 'out', 'adjustment']);  // in: receiving, out: sale, adjustment: inventory correction
    $table->integer('quantity');                         // always positive; type decides if it's added or subtracted
    $table->decimal('unit_cost_usd', 10, 2)->nullable(); // cost at the moment of receiving (for 'in')
    $table->string('reference')->nullable();             // invoice #, sale ID, etc.
    $table->date('date');
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

### 4.5 Sales Table
```php
Schema::create('sales', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // who recorded the sale
    $table->date('date');
    $table->string('customer_name')->nullable();
    $table->enum('currency', ['USD', 'ZIG']);
    $table->enum('payment_method', ['ecocash', 'bank', 'cash']);
    $table->enum('status', ['draft', 'completed', 'paid'])->default('completed'); // draft = not yet saved, completed = recorded but unpaid, paid = completed
    $table->decimal('total_amount', 10, 2);               // total in the sale's currency
    $table->timestamps();
});
```

### 4.6 Sale Items Table
```php
Schema::create('sale_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->integer('quantity');
    $table->decimal('unit_price', 10, 2);                // price in the sale's currency
    $table->decimal('total', 10, 2);                     // quantity * unit_price
    $table->timestamps();
});
```

> **Important**: We do **not** store exchange rates in the sale. We keep USD and ZIG totals **separately** in reports by summing `total_amount` per currency.

---

## 5. Eloquent Models and Relationships

### Product Model (`app/Models/Product.php`)
```php
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'sku', 'description', 'category_id', 'supplier_id',
        'cost_price', 'price_usd', 'price_zig',
        'current_quantity', 'reorder_level'
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }
}
```

### Sale Model (`app/Models/Sale.php`)
```php
class Sale extends Model
{
    protected $fillable = [
        'user_id', 'date', 'customer_name', 'currency', 'payment_method', 'status', 'total_amount'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }
}
```

### SaleItem Model (`app/Models/SaleItem.php`)
```php
class SaleItem extends Model
{
    protected $fillable = ['sale_id', 'product_id', 'quantity', 'unit_price', 'total'];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
```

### StockMovement Model (`app/Models/StockMovement.php`)
```php
class StockMovement extends Model
{
    protected $fillable = [
        'product_id', 'user_id', 'type', 'quantity', 'unit_cost_usd',
        'reference', 'date', 'notes'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
```

---

## 6. Business Logic (Backend)

### 6.1 Product CRUD
- Use standard Laravel controllers with Inertia responses.
- Validation rules: `name` required, `sku` unique, prices numeric ≥0, `reorder_level` integer ≥0.
- Store/update product.

### 6.2 Stock Receiving (Stock‑in)
- Create a `StockMovement` with `type = 'in'`, quantity, `unit_cost_usd` (current cost), `user_id = Auth::id()`.
- **Update** `Product::current_quantity += quantity`.
- Wrap in a database transaction.

### 6.2b Stock Adjustments (Inventory Corrections)
- For discrepancies or damage, create a `StockMovement` with `type = 'adjustment'`.
- If quantity is positive, it's an addition (found extra); if negative, it's a loss (damage/shrinkage).
- Update `Product::current_quantity` accordingly.
- Require notes explaining the adjustment.

### 6.3 Sale Recording (Stock‑out)
- Validate that enough stock exists for each product.
- Create `Sale` header with selected `currency`, `payment_method`, `user_id = Auth::id()`, `status = 'completed'`.
- For each item:
  - Create `SaleItem` (unit_price in sale's currency, total).
  - Create `StockMovement` with `type = 'out'`, quantity = sold, `user_id = Auth::id()`, `reference = sale_id`.
  - **Subtract** `Product::current_quantity -= quantity`.
- Compute total and store in `Sale::total_amount`.
- Use transaction to ensure all or nothing.
- **Optional**: if teller needs to undo, set `status = 'draft'` instead of deleting (audit trail).

### 6.4 Low Stock Check
- In the Product model, add a scope:
```php
public function scopeLowStock($query)
{
    return $query->whereColumn('current_quantity', '<=', 'reorder_level');
}
```

### 6.5 Dashboard Stats
- Total products count.
- Low stock count: `Product::lowStock()->count()`.
- Today's sales: `Sale::whereDate('date', today())->get()` → group by `currency` to sum `total_amount`.

---

## 7. Routes & Controllers (Inertia Pages)

Create the following routes in `routes/web.php` (all protected by auth middleware):

```php
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::resource('products', ProductController::class);
    Route::resource('categories', CategoryController::class); // optional but recommended
    Route::resource('suppliers', SupplierController::class);   // optional

    // Stock receiving
    Route::get('/stock/receive', [StockController::class, 'receiveForm'])->name('stock.receive');
    Route::post('/stock/receive', [StockController::class, 'storeReceive']);

    // Sales
    Route::get('/sales/create', [SaleController::class, 'create'])->name('sales.create');
    Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');

    // Reports
    Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
});
```

> **Controller skeletons**:
> - `ProductController`: index, create, store, edit, update, destroy.
> - `SaleController`: create (shows form), store, index (list of sales with filters).
> - `StockController`: receiveForm, storeReceive.
> - `ReportController`: sales (with filters).

All controllers should return `Inertia::render('PageName', $props)`.

---

## 8. Frontend Pages (Vue + shadcn-vue)

### 8.1 Layout
- Use `AppLayout` that includes a sidebar navigation (Breeze already provides one).  
- In the sidebar, add links to: Dashboard, Products, Receive Stock, New Sale, Sales List, Reports.

### 8.2 Products Index Page (`Pages/Products/Index.vue`)
- Use shadcn `Table` to display product list with columns: SKU, Name, Category, Stock, Price USD, Price ZIG, Actions.
- Add a "Low Stock" badge (shadcn `Badge` variant="destructive") when `current_quantity <= reorder_level`.
- Include a search/filter input.
- Pagination using Laravel's `LengthAwarePaginator` and shadcn `Pagination`.
- "Add Product" button opens a `Dialog` with the product form.

### 8.3 Product Form (reusable component)
- Use shadcn `Form`, `Input`, `Select` (for category/supplier).
- Fields: name, sku, description, category (select), supplier (select), cost_price, price_usd, price_zig, reorder_level.
- Submit via Inertia `useForm`.

### 8.4 Receive Stock Page (`Pages/Stock/Receive.vue`)
- Simple form: product select (searchable), quantity, unit cost (USD), date, reference (optional), notes.
- Submit to store.

### 8.5 New Sale Page (`Pages/Sales/Create.vue`) – **the most complex**
- Header:
  - Date picker (use `Input` type="date").
  - Customer Name (Input).
  - Currency (shadcn `Select` with USD/ZIG).
  - Payment Method (shadcn `Select` with Ecocash/Bank/Cash).
  - Status (hidden field, defaults to 'completed'; optional draft mode for later).
- Items table:
  - Dynamic rows: each row has Product Select (searchable), Quantity Input, Unit Price Input (auto-filled based on currency, but editable).
  - "Add Row" button.
  - Each row can be removed.
- Total: computed sum of row totals, displayed prominently.
- Submit button uses `useForm` with `sale` data (header + items array).

**Important**: When currency changes, update each row's `unit_price` to match the product's `price_usd` or `price_zig`.

### 8.6 Sales List Page (`Pages/Sales/Index.vue`)
- Table with columns: Date, Customer, Currency, Payment Method, Total, Actions (view details).
- Filters: date range, currency dropdown, payment method dropdown.
- Use Inertia `get` with query parameters.

### 8.7 Dashboard (`Pages/Dashboard.vue`)
- Use shadcn `Card` grid:
  - Total Products
  - Low Stock Count (click to go to products filtered)
  - Today's Sales in USD (sum)
  - Today's Sales in ZIG (sum)
- A "Low Stock Items" card listing products with low stock (name, current quantity, reorder level).

### 8.8 Reports Page (`Pages/Reports/Sales.vue`)
- Filters: date range, currency (all/USD/ZIG), payment method (all/ecocash/bank/cash).
- Table of sales matching filters with summary totals (per currency if filtered to a single currency, or two rows for both).
- Option to export (CSV) – add later.

---

## 9. Key Implementation Details for the AI

### 9.1 Database Transactions in Controllers
Always wrap stock‑in and sale creation in `DB::transaction()` to maintain consistency.

### 9.2 Stock Movement Update
- For receiving: `$product->current_quantity += $movement->quantity;`
- For sale: `$product->current_quantity -= $item->quantity;`
- Save product after updating.

### 9.3 Validation Rules
- Sale: ensure `items` array has at least one item.
- Each item: product exists, quantity >0 and ≤ current_quantity (for sales).
- For receiving: quantity >0, cost >=0.

### 9.4 Inertia Form Handling
- Use `useForm` for all forms.
- After successful submission, redirect with a flash message (e.g., `session()->flash('success', 'Sale recorded!')`) and display using a toast or `Alert` component.

### 9.5 Filtering on Index Pages
- Use Laravel's query scopes and pagination, passing filter parameters via Inertia `get` requests.

### 9.6 Shadcn Integration
- Use `<template>` with `<Button>`, `<Input>`, `<Select>`, `<Table>`, `<Card>`, `<Badge>`, `<Dialog>`.
- For forms, use `<Form>` from shadcn-vue (based on `vee-validate` and `zod`).  
  Or you can simply use plain `useForm` from Inertia and bind inputs manually (simpler for MVP).

### 9.7 Currency Formatting & Display
- Create a Vue composable `useCurrencyFormat()` that returns a formatter:
  - USD: `USD 50.00` (2 decimals)
  - ZIG: `ZWL 5,000.00` or `ZIG 5,000.00` (2 decimals, thousands separator)
- Use in all product prices, sale totals, and reports.
- Example: `{{ formatCurrency(amount, currency) }}` in templates.
- In controllers, return raw decimal values and format in Vue only (keeps backend clean).

### 9.8 Audit Trail Usage
- On dashboard, optional: show "Recent Movements" with user names (e.g., "Admin received 10 units of Product X").
- On reports, show who recorded each sale/movement (useful for accountability).
- When querying, always include `with(['user'])` to fetch user relationship.

### 9.9 Soft Deletes for Products
- When a product is deleted, it's marked `deleted_at = now()`, not hard-deleted.
- In index pages, exclude soft-deleted products by default (Laravel does this automatically if you use `SoftDeletes`).
- In reports/sales history, include deleted products so historical records remain intact.
- Optional: add a "Restore" action for accidental deletes.
Create seeders:
- `CategorySeeder` with a few categories.
- `SupplierSeeder` with a few suppliers.
- `ProductSeeder` with 20–30 products.
- `StockMovementSeeder` to set initial stock.
- `SaleSeeder` for demo sales (with various currencies and payments).

Run `php artisan db:seed` to populate data for development.

---

## 11. Deployment Notes (Optional)
- Set `APP_ENV=production` and optimize.
- Use `php artisan migrate` on production.
- Set up a cron job for any scheduled tasks (not needed now).

---

## 12. Future Enhancements (out of scope for MVP)
- POS interface with barcode scanning.
- Customer management.
- Bulk product import via CSV.
- Email low‑stock alerts.
- Split payments / partial payments.

---

## 13. Current Improvement Pass

Implement these corrections before adding new features:

1. Prevent overselling in sale recording:
   - Group sale quantities by product before validation.
   - Lock product rows inside the sale transaction before checking and subtracting stock.
   - Reject duplicate-row totals that exceed available stock.

2. Preserve stock adjustment direction:
   - Store adjustment movement quantities with their sign, or otherwise persist direction.
   - Prevent removals that would make product stock negative.

3. Correct supplier sales reporting:
   - Calculate supplier sales from `sale_items.total`, joined through products and sales.
   - Do not credit a supplier with the full sale total when a sale contains products from multiple suppliers.

4. Preserve archived product history:
   - Load soft-deleted products on historical sale item relationships.
   - Keep sale detail pages useful after a product is archived.

5. Restore automated test isolation:
   - Enable `RefreshDatabase` for feature tests using the in-memory SQLite database.
   - Run `php artisan test`.

6. Fix TypeScript checks:
   - Use Wayfinder `.url()` / `.form()` outputs consistently where Inertia expects strings or form definitions.
   - Tighten UI select/input handler types.
   - Run `npm run types:check`.

7. Remove starter-kit polish leftovers:
   - Replace Laravel starter kit footer links with POS-appropriate links or remove them.
   - Align sale status display with the database values: `draft`, `completed`, `paid`.

Verification:
- `php artisan test`
- `npm run types:check`
- `npm run build`

---

## Summary for the AI
This `plan.md` contains every detail needed to build the entire application.  
The AI should start by **installing dependencies**, **creating migrations**, **writing models**, **building controllers and routes**, and then **developing each Vue page** with shadcn components following the order above.  
All logic must respect the dual currency and separate reporting requirements.
