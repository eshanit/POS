Brilliant! Adding **expiry tracking**, **fast movers**, and **slow movers** turns this from a simple stock count into a **full retail intelligence tool**. 

To do this properly, we need to add an `expiry_date` field to the `products` table (simple approach for MVP) and pull sales data from `sale_items` to calculate movement velocity.

Here is your **updated and complete `inventory_report.md`**. **Replace your old version with this one**—it contains everything from before, plus the new analytics sections.

---

```markdown
# Feature Specification: Inventory Valuation, Movement Analytics & Expiry Report

**Parent Plan:** `plan.md`  
**Context:** This defines the **Inventory Report** page for the Laravel + Inertia + Vue + Shadcn app. 
**Goal:** Provide the shop owner with a complete financial & operational snapshot—including which products make money, which are dying on the shelf, and which are about to expire.

---

## 1. Page Objective (Expanded)
- Show **real-time valuation** of current stock (Cost vs. Potential Revenue).
- Highlight **actionable stock statuses** (Low Stock, Out of Stock).
- **Identify Top 5 Fastest Movers** (products selling the most units in a given period).
- **Identify Top 5 Slowest Movers / "Shelf Warmers"** (products that haven't sold in the longest time or have the lowest velocity).
- **Flag products nearing expiry** (within the next 30 days).
- Allow filtering by **Category**, **Stock Status**, and **Date Range** (for movement).
- **Export** all views to CSV for accountant reconciliation.

---

## 2. Database Schema Update
**Add `expiry_date` to the `products` table** (new migration):

```php
Schema::table('products', function (Blueprint $table) {
    $table->date('expiry_date')->nullable()->after('reorder_level');
});
```

> **Note to AI**: Update the Product model's `$fillable` and the Product Create/Edit Vue forms to include `expiry_date` (Input type="date").

---

## 3. UI Layout (Shadcn Components)

The page will consist of **four** distinct vertical sections.

### 3.1 Filter Bar (Top)
- **Layout**: Horizontal flex row, wrapped in a shadcn `Card`.
- **Components**:
  1. **Date Range Picker** (for movement analytics): Start Date & End Date (defaults to `last 30 days`).
  2. **Category Filter** (`Select`) – Options: "All Categories" + list.
  3. **Stock Status Filter** (`Select`) – All / In Stock / Low Stock / Out of Stock.
  4. **[Refresh]** Button – triggers Inertia `get`.
  5. **[Export CSV]** Button – exports the currently filtered *valuation table*.

---

### 3.2 Summary KPI Cards (Row 1)
- **Layout**: Grid of 5 shadcn `Card` components.
- **Card 1 - Total Items**: Sum of all `current_quantity`.
- **Card 2 - Low Stock Alerts**: Count of products meeting the "Low Stock" criteria.
- **Card 3 - Total Cost (USD)**: Sum of (`current_quantity * cost_price`).
- **Card 4 - Potential Revenue**: **Two lines**: `USD $X` and `ZIG ZIG X`.
- **Card 5 - Expiring Soon**: Count of products where `expiry_date` is between `today()` and `today()->addDays(30)`.

---

### 3.3 Section 2: Movement Analytics (Top 5 Fastest vs Slowest)
*These are displayed side-by-side in a 2-column grid.*

#### Left Column: 🚀 Top 5 Fastest Movers
- **Logic**: Sum of `sale_items.quantity` grouped by `product_id` within the selected date range.
- **Display Table** (small, max 5 rows):
  - Rank (1-5).
  - Product Name & SKU.
  - Total Units Sold (in the period).
  - Revenue Generated (in USD and ZIG, based on sale price).
- **Fallback**: If no sales exist, show: *"No sales data for this period."*

#### Right Column: 🐢 Top 5 Slowest Movers (Shelf Warmers)
- **Logic**: Products with the **lowest** total units sold in the selected date range. *Including products with zero sales*—these are the "dead stock" items.
- **Display Table**:
  - Rank (1-5).
  - Product Name & SKU.
  - Total Units Sold (in the period) – show `0` if none.
  - Days Since Last Sale (if applicable).
  - **Current Qty on Hand** – to show how much is sitting idle.
- **Fallback**: If all products have sales, just show the 5 with the lowest volume.

---

### 3.4 Section 3: Expiry Alerts Table
- **Layout**: A dedicated card titled **"⚠️ Products Nearing Expiry (Next 30 Days)"**.
- **Columns**:
  - Product Name.
  - Expiry Date (formatted).
  - **Days Until Expiry** (e.g., "15 days").
  - Current Qty on Hand.
  - Potential Loss (USD) = `qty * cost_price` – *so he knows how much money he'll lose if it expires*.
- **Sorting**: Default sort by `expiry_date` ascending (soonest first).
- **Conditional Styling**: 
  - `<= 7 days` → Red background / Destructive badge.
  - `8-30 days` → Yellow background / Warning badge.

---

### 3.5 Section 4: Detailed Valuation Table (Same as before)
- Full table with **SKU, Name, Category, Qty, Cost, Total Cost, Sell Price, Potential Rev, Status**.
- **Sticky Footer** with Grand Totals (Total Qty, Total Cost USD, Total Rev USD, Total Rev ZIG, Projected Gross Profit).

---

## 4. Backend Logic (Laravel)

### 4.1 Controller Method: `ReportController@inventory()`
**Inputs**: Accepts `start_date`, `end_date`, `category_id`, `stock_status`.

**Step 1: Base Product Query**
- `Product::with('category')` with filters applied.

**Step 2: Valuation Aggregations** (for cards & footer)
- Run separate aggregate queries (as described previously) to get totals.

**Step 3: Movement Analytics (Fast & Slow)**
- Query `SaleItem` joined with `Sale` (to filter by date) and `Product`.
- **Fastest**:
  ```php
  $fastest = SaleItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
      ->whereHas('sale', function($q) use ($start_date, $end_date) {
          $q->whereBetween('date', [$start_date, $end_date]);
      })
      ->groupBy('product_id')
      ->orderByDesc('total_sold')
      ->limit(5)
      ->with('product')
      ->get();
  ```
- **Slowest**:
  - Get all products with their sale totals (including zeros). If a product has no sale, its total is 0.
  - *Optimization:* Get all product IDs, fetch the sales sums, merge them, sort by total_sold ascending, take 5.

**Step 4: Expiry Alerts**
- Fetch `Product::whereNotNull('expiry_date')
          ->where('expiry_date', '>=', now())
          ->where('expiry_date', '<=', now()->addDays(30))
          ->where('current_quantity', '>', 0)
          ->orderBy('expiry_date')
          ->get();`

### 4.2 CSV Export
- Export the **Valuation Table** data only (to keep the file clean).
- Optionally, export the Expiry Alerts table as a separate CSV.

---

## 5. Frontend Vue Implementation (Shadcn specifics)

### 5.1 Layout Structure
```vue
<template>
  <div class="space-y-6">
    <!-- Filter Bar -->
    <Card> ... </Card>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4"> ... </div>

    <!-- Movement Analytics (2 cols) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <Card title="Top 5 Fastest Movers"> ... </Card>
      <Card title="Top 5 Slowest Movers"> ... </Card>
    </div>

    <!-- Expiry Alerts -->
    <Card title="Expiring Soon"> ... </Card>

    <!-- Valuation Table -->
    <Card title="Current Stock Valuation"> ... </Card>
  </div>
</template>
```

### 5.2 Expiry Countdown Badge
```vue
<Badge :variant="days <= 7 ? 'destructive' : 'warning'">
  {{ days }} days left
</Badge>
```

### 5.3 Handling Zero Sales for Slow Movers
When calculating slow movers, manually inject products with zero sales into the collection so they appear in the list.

**Backend Helper Logic**:
```php
// Get all product IDs
$allProductIds = Product::pluck('id')->toArray();
// Get sales sums for the period
$salesData = SaleItem::...->groupBy('product_id')->pluck('total_sold', 'product_id')->toArray();
// Merge: assign 0 to products with no sales
$movementData = [];
foreach ($allProductIds as $id) {
    $movementData[$id] = $salesData[$id] ?? 0;
}
// Sort and take top/bottom 5
```

---

## 6. Step-by-Step Development Checklist (for AI)

- [ ] Create migration to add `expiry_date` to `products` table.
- [ ] Update Product Model, Controller (Store/Update), and Vue Form to handle `expiry_date`.
- [ ] Update `ReportController@inventory` to accept `start_date` & `end_date`.
- [ ] Implement Fastest Movers query.
- [ ] Implement Slowest Movers query (including products with zero sales).
- [ ] Implement Expiry Alerts query.
- [ ] Create/Update Vue page: `resources/js/Pages/Reports/Inventory.vue`.
- [ ] Add the new Shadcn components (if not already imported).
- [ ] Implement the 2-column grid layout for movement analytics.
- [ ] Style the expiry countdown with proper color coding.
- [ ] Update CSV export to include the new columns (or create separate exports).
- [ ] Test with seeded data (create sales over varying dates to validate movement).

---

## 7. Business Logic Rules (Additions)
- **Expiry Date**: Optional. If a product has no expiry date, it is ignored in the "Expiring Soon" section.
- **Movement Period**: Defaults to **last 30 days**. The user can change this using the date pickers.
- **Zero Sales**: When calculating slow movers, products with **zero sales** are prioritized (they are the "worst" performers).
- **Days Since Last Sale**: If a product has zero sales in the period but had sales previously, we don't calculate "days since" for MVP (just show a hyphen or "No sales").

---

**This enhanced report gives the client a full 360° view of his shop—moving from a simple "paper book" to an intelligent business management tool!**
```