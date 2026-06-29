Perfect! Here is your standalone **`inventory_report.md`** file. 

It is fully self-contained and acts as a direct extension of your main `plan.md`. Feed this to DeepSeek Flash alongside the plan, and it will have everything it needs to build a professional, valuation-focused inventory report page for your client.

---

```markdown
# Feature Specification: Inventory Valuation & Status Report

**Parent Plan:** `plan.md`  
**Context:** This document defines the dedicated **Inventory Report** page for the Laravel + Inertia + Vue + Shadcn inventory app. 
**Goal:** Provide the shop owner with a financial "snapshot" of their stock—moving beyond physical counts to show the actual money tied up in inventory and potential revenue.

---

## 1. Page Objective
- Show **real-time valuation** of current stock (Cost vs. Potential Revenue).
- Highlight **actionable stock statuses** (Low Stock, Out of Stock).
- Allow filtering by **Category** and **Stock Status**.
- Display aggregated **Grand Totals** at the bottom of the table.
- **Export** data to CSV for accountant reconciliation.

---

## 2. Route & Access
- **URL**: `/inventory-report`
- **Method**: GET (Inertia render)
- **Controller**: `ReportController@inventory` (new method)
- **Navigation**: Add a sidebar link labeled "Inventory Report" in the main layout.

---

## 3. UI Layout (Shadcn Components)

The page will consist of three distinct vertical sections.

### 3.1 Filter Bar (Top)
- **Layout**: Horizontal flex row, wrapped in a shadcn `Card`.
- **Components**:
  1. **Date Picker** (`Input` type="date") – Label: "As of Date" (defaults to `today()`).
  2. **Category Filter** (`Select`) – Options: "All Categories" + list from `categories` table.
  3. **Stock Status Filter** (`Select`) – Options: 
     - All
     - In Stock (`current_quantity > reorder_level`)
     - Low Stock (`current_quantity <= reorder_level AND > 0`)
     - Out of Stock (`current_quantity <= 0`)
  4. **[Refresh]** Button (shadcn `Button` variant="outline") – triggers Inertia `get` with query params.
  5. **[Export CSV]** Button (shadcn `Button` variant="default") – downloads the current filtered dataset.

### 3.2 Summary KPI Cards
- **Layout**: Grid of 4 shadcn `Card` components (responsive: 4 columns on large screens, 2 on tablet, 1 on mobile).
- **Card 1 - Total Items**: Sum of all `current_quantity` across filtered products.
- **Card 2 - Low Stock Alerts**: Count of products meeting the "Low Stock" criteria.
- **Card 3 - 💰 Total Cost (USD)**: Sum of (`current_quantity * cost_price`). *This tells the owner how much capital is sitting on the shelf.*
- **Card 4 - 📈 Potential Revenue**: Displayed as **two lines** inside this card:
  - `USD: $X,XXX.XX`
  - `ZIG: ZIG X,XXX.XX` 
  *(Sum of `current_quantity * price_usd` and `current_quantity * price_zig`).*

### 3.3 Detailed Valuation Table
- **Component**: shadcn `Table` with a **sticky footer** (the footer stays visible when scrolling down the table).
- **Columns** (Left to right):

| Column Header | Data Source | Formatting / Notes |
| :--- | :--- | :--- |
| **SKU** | `products.sku` | Bold text. |
| **Product Name** | `products.name` | Primary text. |
| **Category** | `categories.name` | Muted text (fallback to "Uncategorized"). |
| **Qty on Hand** | `products.current_quantity` | Integer. Highlight in **red** if `<= reorder_level`. |
| **Cost (USD)** | `products.cost_price` | 2 decimal places. |
| **Total Cost (USD)** | `qty * cost_price` | **Computed**. 2 decimal places. |
| **Sell (USD)** | `products.price_usd` | 2 decimal places. |
| **Sell (ZIG)** | `products.price_zig` | 2 decimal places. |
| **Potential Rev (USD)** | `qty * price_usd` | **Computed**. 2 decimal places. |
| **Potential Rev (ZIG)** | `qty * price_zig` | **Computed**. 2 decimal places. |
| **Status** | Compare `qty` vs `reorder_level` | **Badge**: `destructive` (Out of Stock), `warning` (Low Stock), `success` (In Stock). |

### 3.4 Table Sticky Footer (Grand Totals)
- Spanning the bottom of the table.
- **Total Qty**: Sum of quantities.
- **Total Cost (USD)**: `$ XXX.XX` (Global sum of column 6).
- **Total Rev (USD)**: `$ XXX.XX` (Global sum of column 9).
- **Total Rev (ZIG)**: `ZIG XXX.XX` (Global sum of column 10).
- **Projected Gross Profit (USD)**: `Total Rev (USD) - Total Cost (USD)`. Display this prominently in **green** if positive.

---

## 4. Backend Logic (Laravel)

### 4.1 Controller Method: `ReportController::inventory()`
- **Inputs**: Accepts `date`, `category_id`, `stock_status` via `Request`.
- **Query**: 
  - Start with `Product::with('category')`.
  - Filter by `category_id` if provided.
  - Apply `stock_status` scope (create local scopes in Product model: `scopeInStock`, `scopeLowStock`, `scopeOutOfStock`).
- **Pagination**: Use `paginate(15)` for the main table data.
- **Summations (for Cards & Footer)**:
  - **Important:** To get accurate footer totals while paginating, **do not** rely on the paginated collection. Run a separate aggregation query **without** `paginate` (but with the same filters) to get:
    - `total_items_sum`
    - `total_cost_sum`
    - `total_rev_usd_sum`
    - `total_rev_zig_sum`
  - *Optimization Tip:* Use `DB::raw` or `selectRaw` for these aggregates to keep database load minimal.

### 4.2 Local Scopes (Product Model)
```php
public function scopeInStock($query) {
    return $query->whereColumn('current_quantity', '>', 'reorder_level');
}
public function scopeLowStock($query) {
    return $query->whereColumn('current_quantity', '<=', 'reorder_level')
                 ->where('current_quantity', '>', 0);
}
public function scopeOutOfStock($query) {
    return $query->where('current_quantity', '<=', 0);
}
```

### 4.3 CSV Export
- Create a `ReportController@exportInventory` route that accepts the same filters via `GET`.
- Generate a CSV stream using `fputcsv` or Laravel's `Collection->toCsv()`.
- Headers: SKU, Product, Category, Qty, Cost Price, Total Cost, Price USD, Price ZIG, Potential Rev USD, Potential Rev ZIG, Status.
- Return as a downloadable `StreamedResponse`.

---

## 5. Frontend Vue Implementation (Shadcn specifics)

### 5.1 Reactive State & Inertia
- Use `useForm` from Inertia for the filter bar to handle query parameters (or just use `router.get` with manual params).
- Watch the `stock_status` select to clear filters appropriately.

### 5.2 Dynamic Badges
```vue
<Badge :variant="product.current_quantity <= 0 ? 'destructive' : 
                 product.current_quantity <= product.reorder_level ? 'warning' : 'success'">
  {{ product.current_quantity <= 0 ? 'Out of Stock' : 
     product.current_quantity <= product.reorder_level ? 'Low Stock' : 'In Stock' }}
</Badge>
```
*(Note: Shadcn `warning` variant may need to be added to your `components.json` or manually styled with Tailwind yellow).*

### 5.3 Sticky Footer Implementation
- Wrap the `<TableBody>` in a container with `max-h-[500px] overflow-y-auto`.
- Place the `<TableFooter>` **outside** the scrollable container so it stays fixed at the bottom of the card.

### 5.4 Currency Formatting
- Use a reusable Vue composable or helper function: `formatCurrency(amount, currency)`.
- Append `$` for USD and `ZIG` for ZIG.

---

## 6. Step-by-Step Development Checklist (for AI)

- [ ] Add the `inventory` method to `ReportController`.
- [ ] Add the `exportInventory` CSV method to `ReportController`.
- [ ] Create the local scopes in `app/Models/Product.php`.
- [ ] Create the Vue page: `resources/js/Pages/Reports/Inventory.vue`.
- [ ] Register the routes in `web.php` (Inertia render and CSV download).
- [ ] Add the sidebar navigation link in `AppLayout.vue`.
- [ ] Import required Shadcn components: `Card`, `Table`, `Select`, `Badge`, `Button`, `Input`.
- [ ] Implement the filtering logic (frontend `router.get` + backend query builder).
- [ ] Implement the aggregation queries for summary cards and sticky footer.
- [ ] Test with seeded data to ensure USD and ZIG totals remain strictly separated.
- [ ] Add `success`/`error` flash messages for the CSV export (using Inertia `session` flashes).

---

## 7. Business Logic Rules
Cost Base: All cost valuations (cost_price and Total Cost) are always in USD. ZIG is exclusively for potential selling revenue.

Rounding: All monetary values are stored and displayed with 2 decimal places (decimal(10,2)).

Date Filter: The "As of Date" currently only applies to the reporting timestamp, not to query historical stock. (For MVP, we only show current stock. Historical "as of" stock requires a dedicated stock movement sum query and can be added later).