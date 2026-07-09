<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Web\BaseController;
use App\Models\Request\Request;
use App\Models\Request\RequestBill;
use App\Models\Admin\ServiceLocation;
use Carbon\Carbon;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class NewDashboardController extends BaseController
{
    /** Cache TTL in seconds (10 minutes). */
    private const CACHE_TTL = 600;

    /**
     * Show the New Dashboard page (improved analytics). Does not replace the existing dashboard.
     */
    public function index()
    {
        if (access()->hasRole('user')) {
            return redirect('/create-booking');
        }
        if (access()->hasRole('owner')) {
            return redirect()->route('owner.dashboard');
        }
        if (access()->hasRole('dispatcher')) {
            return redirect()->route('dispatch.dashboard');
        }
        if (access()->hasRole('employee')) {
            return redirect('/support-tickets');
        }
        if (access()->hasRole('franchise_owner')) {
            return redirect()->route('franchiseowner.dashboard');
        }

        $locationIds = get_user_location_ids(auth()->user());
        $serviceLocations = ServiceLocation::whereIn('id', $locationIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]);

        return Inertia::render('pages/new-dashboard/index', [
            'serviceLocations' => $serviceLocations,
        ]);
    }

    /**
     * Analytics data for New Dashboard: trips per day, revenue per zone, cancellation reasons.
     * Results are cached to avoid heavy aggregations on every load.
     */
    public function analytics(HttpRequest $request)
    {
        $serviceLocationId = $request->input('service_location_id');
        $locationIds = get_user_location_ids(auth()->user());
        $cacheKey = 'new_dashboard_analytics_' . auth()->id() . '_' . ($serviceLocationId ?: 'all') . '_' . implode('_', $locationIds->toArray());

        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($locationIds, $serviceLocationId) {
            return [
                'trips_per_day' => $this->getTripsPerDay($locationIds, $serviceLocationId),
                'revenue_per_zone' => $this->getRevenuePerZone($locationIds, $serviceLocationId),
                'cancellation_reasons' => $this->getCancellationReasons($locationIds, $serviceLocationId),
                'currency_symbol' => $this->getCurrencySymbol($serviceLocationId),
            ];
        });

        return response()->json($data);
    }

    /**
     * Trips per day for the last 30 days (completed only).
     */
    private function getTripsPerDay($locationIds, $serviceLocationId): array
    {
        $start = Carbon::now()->subDays(30)->startOfDay();
        $end = Carbon::now()->endOfDay();

        $query = Request::query()
            ->companyKey()
            ->where('is_completed', true)
            ->whereIn('service_location_id', $locationIds)
            ->whereBetween('trip_start_time', [$start, $end]);

        if ($serviceLocationId && $serviceLocationId !== 'all') {
            $query->where('service_location_id', $serviceLocationId);
        }

        $counts = $query
            ->selectRaw('DATE(trip_start_time) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $days = [];
        $values = [];
        for ($d = Carbon::now()->subDays(29); $d->lte(Carbon::now()); $d->addDay()) {
            $dateStr = $d->format('Y-m-d');
            $days[] = $d->format('M j');
            $values[] = (int) ($counts[$dateStr] ?? 0);
        }

        return ['labels' => $days, 'values' => $values];
    }

    /**
     * Revenue per service location (zone) for completed trips.
     */
    private function getRevenuePerZone($locationIds, $serviceLocationId): array
    {
        $query = Request::query()
            ->companyKey()
            ->where('is_completed', true)
            ->whereIn('service_location_id', $locationIds)
            ->join('request_bills', 'requests.id', '=', 'request_bills.request_id')
            ->join('service_locations', 'requests.service_location_id', '=', 'service_locations.id')
            ->selectRaw('service_locations.name as zone_name, SUM(request_bills.total_amount) as revenue')
            ->groupBy('requests.service_location_id', 'service_locations.name')
            ->orderByDesc('revenue');

        if ($serviceLocationId && $serviceLocationId !== 'all') {
            $query->where('requests.service_location_id', $serviceLocationId);
        }

        $rows = $query->get();
        return [
            'labels' => $rows->pluck('zone_name')->toArray(),
            'values' => $rows->pluck('revenue')->map(fn ($v) => round((float) $v, 2))->toArray(),
        ];
    }

    /**
     * Cancellation counts by reason (cancel_method: 0=auto, 1=user, 2=driver, 3=dispatcher).
     */
    private function getCancellationReasons($locationIds, $serviceLocationId): array
    {
        $query = Request::query()
            ->companyKey()
            ->where('is_cancelled', true)
            ->whereIn('service_location_id', $locationIds);

        if ($serviceLocationId && $serviceLocationId !== 'all') {
            $query->where('service_location_id', $serviceLocationId);
        }

        $row = $query->selectRaw("
            COUNT(CASE WHEN cancel_method = '0' THEN 1 END) AS auto_cancelled,
            COUNT(CASE WHEN cancel_method = '1' THEN 1 END) AS user_cancelled,
            COUNT(CASE WHEN cancel_method = '2' THEN 1 END) AS driver_cancelled,
            COUNT(CASE WHEN cancel_method = '3' THEN 1 END) AS dispatcher_cancelled
        ")->first();

        $labels = ['Auto (no driver)', 'User', 'Driver', 'Dispatcher'];
        $values = [
            (int) $row->auto_cancelled,
            (int) $row->user_cancelled,
            (int) $row->driver_cancelled,
            (int) $row->dispatcher_cancelled,
        ];

        return ['labels' => $labels, 'values' => $values];
    }

    private function getCurrencySymbol($serviceLocationId): string
    {
        if ($serviceLocationId && $serviceLocationId !== 'all') {
            $service = ServiceLocation::find($serviceLocationId);
            if ($service) {
                return $service->currency_symbol ?? get_settings('currency_symbol');
            }
        }
        return get_settings('currency_symbol') ?? '';
    }
}
