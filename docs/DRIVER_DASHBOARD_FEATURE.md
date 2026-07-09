# Driver Dashboard – Feature spec

A new **Driver Dashboard** under **Driver Management** that shows analytics and charts based on driver records. The main dashboard stays focused on trips/earnings; this one is driver-centric.

---

## 1. Placement

- **Menu:** **Users** → **Driver Management** → **Driver Dashboard** (first item under the Driver Management collapse, or after “Drivers”).
- **Route:** `GET /driver-dashboard` (or `/drivers/dashboard`).
- **Permission:** Reuse `drivers-management` or add a dedicated permission (e.g. `driver-dashboard-view`) under the same menu so only users with driver access see it.

---

## 2. Summary cards (top row)

| Card | Description | Data source |
|------|-------------|-------------|
| **Total drivers** | Count of all drivers (including deleted/declined if you need) | `Driver::count()` scoped by company + service location |
| **Approved drivers** | `approve = 1` | `Driver::where('approve', 1)` |
| **Pending approval** | `approve = 0` (waiting for approval) | `Driver::where('approve', 0)` |
| **Active (online)** | Optional: drivers currently available / on trip (if you have real-time or cached availability) | `Driver::where('approve', 1)->where('active', 1)` or from Firebase/availability table |
| **New this month** | Drivers created in the last 30 days | `Driver::where('created_at', '>=', now()->subDays(30))` |

All counts should respect `get_user_location_ids(auth()->user())` and optional **service location** filter (dropdown “All” / specific zone), same as the main dashboard.

---

## 3. Charts and analytics

### 3.1 Drivers per zone (service location)

- **Type:** Horizontal bar or donut.
- **Data:** Count of drivers per `service_location_id` (name from `service_locations`).
- **Use:** See where drivers are concentrated.

### 3.2 Drivers by vehicle type

- **Type:** Donut or bar.
- **Data:** Count of drivers per `vehicle_type` (or vehicle_type_id from drivers / vehicle_types).
- **Use:** Fleet mix (taxi vs delivery vs etc.).

### 3.3 Driver registrations over time

- **Type:** Line or area chart.
- **Data:** Number of drivers created per week or month (e.g. last 12 months).
- **Use:** Growth and trends.

### 3.4 Acceptance ratio distribution (optional)

- **Type:** Bar chart (buckets: 0–25%, 25–50%, 50–75%, 75–100%).
- **Data:** From `drivers.acceptance_ratio` or computed from `total_accept` / (`total_accept` + `total_reject`).
- **Use:** See how many drivers have low vs high acceptance.

### 3.5 Top drivers by completed trips (last 30 days)

- **Type:** Table or horizontal bar (top 10).
- **Data:** Join `requests` (driver_id, is_completed), filter by `trip_start_time` last 30 days, group by driver_id, count, order by count desc; join driver name and service location.
- **Use:** Recognize best performers.

### 3.6 Top drivers by earnings (last 30 days)

- **Type:** Table or horizontal bar (top 10).
- **Data:** Join `requests` + `request_bills` (driver_id, is_completed), sum `driver_commision` or `total_amount` for last 30 days, group by driver_id.
- **Use:** Revenue contribution per driver.

---

## 4. Data and performance

- **Scoping:** All queries use `companyKey()` and `whereIn('service_location_id', get_user_location_ids(auth()->user()))` (and optional `service_location_id` from filter).
- **Caching:** Consider caching dashboard payload for 5–10 minutes (e.g. `Cache::remember` with key including user id and selected service_location_id) to avoid heavy aggregates on every load.
- **Pagination:** Only needed for “Top drivers” tables if you show more than 10–20 rows.

---

## 5. Implementation outline

1. **Backend**
   - **Controller:** e.g. `DriverDashboardController` with:
     - `index()`: return Inertia page `pages/driver_dashboard/index` with `serviceLocations` (for dropdown) and optionally initial data.
     - `analytics()`: JSON endpoint returning summary counts + chart datasets (drivers per zone, by vehicle type, registrations over time, top drivers by trips, top drivers by earnings). Optional query param: `service_location_id`.
   - **Routes:** `routes/web/driver-management.php` (or equivalent):
     - `Route::get('/driver-dashboard', [DriverDashboardController::class, 'index'])->name('driver-dashboard');`
     - `Route::get('/driver-dashboard/analytics', [DriverDashboardController::class, 'analytics'])->name('driver-dashboard.analytics');`
   - **Permission:** Either reuse `drivers-management` or add `driver-dashboard-view` and register in seeder; protect both routes with the same middleware as other driver management pages.

2. **Frontend**
   - **Page:** `resources/js/Pages/pages/driver_dashboard/index.vue` (or `driver-management/dashboard.vue`).
   - **Layout:** Same as main dashboard: Layout, PageHeader, service location dropdown, summary cards row, then rows of charts (ApexCharts: donut/bar/area like New Dashboard).
   - **Data:** On mount (and when service location changes), call `/driver-dashboard/analytics?service_location_id=...` and map response to chart options and series.
   - **i18n:** Add keys for “Driver Dashboard”, “Total drivers”, “Approved drivers”, “Pending approval”, “Active”, “New this month”, “Drivers per zone”, “Drivers by vehicle type”, “Registrations over time”, “Top drivers by trips”, “Top drivers by earnings”, etc., in `public/lang/*/view_pages_*.json` or `pages_names.json`.

3. **Menu**
   - In `resources/js/Components/menu.vue`, inside the Driver Management collapse (`#driver`), add a first item:
     - `<li class="nav-item" v-if="permissions.includes('drivers-management')"><Link href="/driver-dashboard" class="nav-link">Driver Dashboard</Link></li>`
   - Use the same permission as other driver management links unless you introduce `driver-dashboard-view`.

---

## 6. Optional enhancements

- **Document expiry:** Count of drivers with documents expiring in next 7/30 days (if you have document expiry data).
- **Negative balance:** Count of drivers with negative wallet balance (link to existing negative-balance-drivers page).
- **Export:** Button to export “Top drivers” or summary to Excel/PDF reusing existing report patterns.

This gives you a single, driver-focused analytics page under Driver Management with summary cards and charts driven by existing driver and request data.
