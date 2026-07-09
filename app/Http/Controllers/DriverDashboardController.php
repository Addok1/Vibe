<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Web\BaseController;
use App\Models\Admin\Driver;
use App\Models\Admin\ServiceLocation;
use App\Models\Request\Request;
use Carbon\Carbon;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class DriverDashboardController extends BaseController
{
    private const CACHE_TTL = 600;

    public function index()
    {
        $locationIds = get_user_location_ids(auth()->user());
        $serviceLocations = ServiceLocation::whereIn('id', $locationIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($s) => ['id' => $s->id, 'name' => $s->name]);

        return Inertia::render('pages/driver_dashboard/index', [
            'serviceLocations' => $serviceLocations,
        ]);
    }

    public function analytics(HttpRequest $request)
    {
        $serviceLocationId = $request->input('service_location_id');
        // $locationIds = get_user_location_ids(auth()->user());
        $locationIds = collect(get_user_location_ids(auth()->user()))->filter()->values();
        // $cacheKey = 'driver_dashboard_' . auth()->id() . '_' . ($serviceLocationId ?: 'all') . '_' . implode('_', $locationIds->toArray());
        $cacheKey = 'driver_dashboard_' . auth()->id() . '_' . ($serviceLocationId ?: 'all') . '_' . implode('_', $locationIds->toArray());

        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($locationIds, $serviceLocationId) {
            return [
            'summary' => $this->getSummary($locationIds, $serviceLocationId),
            'drivers_per_zone' => $this->getDriversPerZone($locationIds, $serviceLocationId),
            'drivers_by_vehicle_type' => $this->getDriversByVehicleType($locationIds, $serviceLocationId),
            'registrations_over_time' => $this->getRegistrationsOverTime($locationIds, $serviceLocationId),
            'acceptance_ratio_buckets' => $this->getAcceptanceRatioBuckets($locationIds, $serviceLocationId),
            'top_drivers_by_trips' => $this->getTopDriversByTrips($locationIds, $serviceLocationId),
            'top_drivers_by_earnings' => $this->getTopDriversByEarnings($locationIds, $serviceLocationId),
            'currency_symbol' => $this->getCurrencySymbol($serviceLocationId),
            ];
        });

        return response()->json($data);
    }

    public function export(HttpRequest $request)
    {
        $serviceLocationId = $request->input('service_location_id', 'all');
        $format = $request->input('format', 'xlsx');
        // $locationIds = get_user_location_ids(auth()->user());
        $locationIds = collect(get_user_location_ids(auth()->user()))->filter()->values();
        // $cacheKey = 'driver_dashboard_' . auth()->id() . '_' . ($serviceLocationId ?: 'all') . '_' . implode('_', $locationIds->toArray());
        $cacheKey = 'driver_dashboard_' . auth()->id() . '_' . ($serviceLocationId ?: 'all') . '_' . implode('_', $locationIds->toArray());
        $data = Cache::get($cacheKey);
        if (!$data) {
            $data = [
                'summary' => $this->getSummary($locationIds, $serviceLocationId),
                'top_drivers_by_trips' => $this->getTopDriversByTrips($locationIds, $serviceLocationId),
                'top_drivers_by_earnings' => $this->getTopDriversByEarnings($locationIds, $serviceLocationId),
                'currency_symbol' => $this->getCurrencySymbol($serviceLocationId),
            ];
        }
        if ($format === 'xlsx' || $format === 'csv') {
            return Excel::download(
                new \App\Exports\DriverDashboardExport($data),
                'driver-dashboard.' . ($format === 'xlsx' ? 'xlsx' : 'csv'),
                $format === 'csv' ?\Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );
        }
        if ($format === 'pdf') {
            $pdf = Pdf::loadView('driver_dashboard.pdf', $data);
            return $pdf->download('driver-dashboard.pdf');
        }
        return response()->json(['error' => 'Invalid format'], 400);
    }

    // private function baseDriverQuery($locationIds, $serviceLocationId)
    // {
    //     $q = Driver::query()
    //         ->whereNull('owner_id')
    //         ->whereNull('fleet_id')
    //         ->whereNull('franchise_owner_id')
    //         ->whereHas('user', fn ($query) => $query->companyKey())
    //         ->whereIn('service_location_id', $locationIds);
    //     if ($serviceLocationId && $serviceLocationId !== 'all') {
    //         $q->where('service_location_id', $serviceLocationId);
    //     }
    //     return $q;
    // }

    private function baseDriverQuery($locationIds, $serviceLocationId)
    {
        $locationIds = collect($locationIds)->filter()->values();

        $q = Driver::query()
            ->whereNull('owner_id')
            ->whereNull('fleet_id')
            ->whereNull('franchise_owner_id')
            ->whereHas('user', fn($query) => $query->companyKey())
            ->whereIn('drivers.service_location_id', $locationIds);

        if (is_numeric($serviceLocationId)) {
            $q->where('drivers.service_location_id', (int)$serviceLocationId);
        }

        return $q;
    }

    private function getSummary($locationIds, $serviceLocationId): array
    {
        $base = $this->baseDriverQuery($locationIds, $serviceLocationId);
        $thirtyDaysAgo = Carbon::now()->subDays(30)->startOfDay();
        $today = Carbon::now()->startOfDay();

        // Optional: document expiry – drivers with at least one document expiring in 7/30 days
        $docExpiring7 = (clone $base)->whereHas('driverDocument', function ($q) use ($today) {
            $q->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '>=', $today)
                ->whereDate('expiry_date', '<=', $today->copy()->addDays(7));
        })->count();
        $docExpiring30 = (clone $base)->whereHas('driverDocument', function ($q) use ($today) {
            $q->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '>=', $today)
                ->whereDate('expiry_date', '<=', $today->copy()->addDays(30));
        })->count();

        // Optional: negative balance count (same threshold as negative-balance-drivers list)
        $threshold = get_settings('driver_wallet_minimum_amount_to_get_an_order') ?? -300;
        $negativeBalance = (clone $base)->whereHas('driverWallet', function ($q) use ($threshold) {
            $q->where('amount_balance', '<', $threshold);
        })->count();

        return [
            'total' => (clone $base)->count(),
            'approved' => (clone $base)->where('approve', 1)->count(),
            'pending' => (clone $base)->where('approve', 0)->count(),
            'active' => (clone $base)->where('approve', 1)->where('active', 1)->count(),
            'new_this_month' => (clone $base)->where('created_at', '>=', $thirtyDaysAgo)->count(),
            'documents_expiring_7_days' => $docExpiring7,
            'documents_expiring_30_days' => $docExpiring30,
            'negative_balance' => $negativeBalance,
        ];
    }

    private function getDriversPerZone($locationIds, $serviceLocationId): array
    {
        $q = Driver::query()
            ->whereNull('owner_id')
            ->whereNull('fleet_id')
            ->whereNull('franchise_owner_id')
            ->whereHas('user', fn($query) => $query->companyKey())
            ->whereIn('drivers.service_location_id', $locationIds)
            ->join('service_locations', 'drivers.service_location_id', '=', 'service_locations.id')
            ->selectRaw('service_locations.name as zone_name, COUNT(drivers.id) as count')
            ->groupBy('drivers.service_location_id', 'service_locations.name')
            ->orderByDesc('count');

        if ($serviceLocationId && $serviceLocationId !== 'all') {
            $q->where('drivers.service_location_id', $serviceLocationId);
        }

        $rows = $q->get();
        return ['labels' => $rows->pluck('zone_name')->toArray(), 'values' => $rows->pluck('count')->map(fn($v) => (int)$v)->toArray()];
    }

    private function getDriversByVehicleType($locationIds, $serviceLocationId): array
    {
        $q = Driver::query()
            ->whereNull('owner_id')
            ->whereNull('fleet_id')
            ->whereNull('franchise_owner_id')
            ->whereHas('user', fn($query) => $query->companyKey())
            ->whereIn('drivers.service_location_id', $locationIds);

        if ($serviceLocationId && $serviceLocationId !== 'all') {
            $q->where('drivers.service_location_id', $serviceLocationId);
        }

        $q->leftJoin('vehicle_types', 'drivers.vehicle_type', '=', 'vehicle_types.id')
            ->selectRaw('COALESCE(vehicle_types.name, "Other") as type_name, COUNT(drivers.id) as count')
            ->groupBy('drivers.vehicle_type', 'vehicle_types.name')
            ->orderByDesc('count');

        $rows = $q->get();
        $labels = $rows->pluck('type_name')->toArray();
        $values = $rows->pluck('count')->map(fn($v) => (int)$v)->toArray();
        return ['labels' => $labels, 'values' => $values];
    }

    private function getRegistrationsOverTime($locationIds, $serviceLocationId): array
    {
        $q = $this->baseDriverQuery($locationIds, $serviceLocationId);
        $start = Carbon::now()->subMonths(12)->startOfMonth();
        $counts = $q->where('created_at', '>=', $start)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();

        $labels = [];
        $values = [];
        for ($d = Carbon::now()->subMonths(11)->startOfMonth(); $d->lte(Carbon::now()); $d->addMonth()) {
            $key = $d->format('Y-m');
            $labels[] = $d->format('M Y');
            $values[] = (int)($counts[$key] ?? 0);
        }
        return ['labels' => $labels, 'values' => $values];
    }

    private function getAcceptanceRatioBuckets($locationIds, $serviceLocationId): array
    {
        $q = $this->baseDriverQuery($locationIds, $serviceLocationId);
        $q->selectRaw("
            SUM(CASE WHEN COALESCE(acceptance_ratio, 0) < 25 THEN 1 ELSE 0 END) AS bucket_0_25,
            SUM(CASE WHEN COALESCE(acceptance_ratio, 0) >= 25 AND COALESCE(acceptance_ratio, 0) < 50 THEN 1 ELSE 0 END) AS bucket_25_50,
            SUM(CASE WHEN COALESCE(acceptance_ratio, 0) >= 50 AND COALESCE(acceptance_ratio, 0) < 75 THEN 1 ELSE 0 END) AS bucket_50_75,
            SUM(CASE WHEN COALESCE(acceptance_ratio, 0) >= 75 THEN 1 ELSE 0 END) AS bucket_75_100
        ");
        // $row = $q->first();
        $row = $q->first() ?? (object)[];
        $labels = ['0-25%', '25-50%', '50-75%', '75-100%'];
        $values = [
            (int)($row->bucket_0_25 ?? 0),
            (int)($row->bucket_25_50 ?? 0),
            (int)($row->bucket_50_75 ?? 0),
            (int)($row->bucket_75_100 ?? 0),
        ];
        return ['labels' => $labels, 'values' => $values];
    }

    private function getTopDriversByTrips($locationIds, $serviceLocationId): array
    {
        $start = Carbon::now()->subDays(30)->startOfDay();
        $end = Carbon::now()->endOfDay();
        $q = Request::query()
            ->companyKey()
            ->where('is_completed', true)
            ->whereNotNull('driver_id')
            ->whereIn('requests.service_location_id', $locationIds)
            ->whereBetween('trip_start_time', [$start, $end]);
        if ($serviceLocationId && $serviceLocationId !== 'all') {
            $q->where('requests.service_location_id', $serviceLocationId);
        }
        $rows = $q->join('drivers', 'requests.driver_id', '=', 'drivers.id')
            ->selectRaw('drivers.id, drivers.name, COUNT(requests.id) as trip_count')
            ->groupBy('drivers.id', 'drivers.name')
            ->orderByDesc('trip_count')
            ->limit(10)
            ->get();
        return $rows->map(fn($r) => ['id' => $r->id, 'name' => $r->name, 'trip_count' => (int)$r->trip_count])->toArray();
    }

    private function getTopDriversByEarnings($locationIds, $serviceLocationId): array
    {
        $start = Carbon::now()->subDays(30)->startOfDay();
        $end = Carbon::now()->endOfDay();
        $q = Request::query()
            ->companyKey()
            ->where('is_completed', true)
            ->whereNotNull('driver_id')
            ->whereIn('requests.service_location_id', $locationIds)
            ->whereBetween('trip_start_time', [$start, $end]);
        if ($serviceLocationId && $serviceLocationId !== 'all') {
            $q->where('requests.service_location_id', $serviceLocationId);
        }
        $rows = $q->join('request_bills', 'requests.id', '=', 'request_bills.request_id')
            ->join('drivers', 'requests.driver_id', '=', 'drivers.id')
            ->selectRaw('drivers.id, drivers.name, SUM(request_bills.driver_commision) as total_earnings')
            ->groupBy('drivers.id', 'drivers.name')
            ->orderByDesc('total_earnings')
            ->limit(10)
            ->get();
        return $rows->map(fn($r) => ['id' => $r->id, 'name' => $r->name, 'earnings' => round((float)$r->total_earnings, 2)])->toArray();
    }

    private function getCurrencySymbol($serviceLocationId): string
    {
        if ($serviceLocationId && $serviceLocationId !== 'all') {
            $s = ServiceLocation::find($serviceLocationId);
            if ($s) {
                return $s->currency_symbol ?? get_settings('currency_symbol');
            }
        }
        return get_settings('currency_symbol') ?? '';
    }
}