<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManagerDashboardController extends Controller
{
    public function index(Request $request)
    {
        [$start, $end] = $this->dateRange($request);
        $hotelIds = $request->user()->managedHotels()
            ->wherePivot('is_active', true)
            ->where('hotels.is_system', false)
            ->pluck('hotels.id');

        $hotels = DB::table('hotels')->whereIn('id', $hotelIds)->whereNull('deleted_at')->get(['id', 'name', 'is_active']);
        $bookings = DB::table('bookings')->whereIn('hotel_id', $hotelIds)->whereNull('deleted_at');
        $transactions = DB::table('menu_transactions')->whereIn('hotel_id', $hotelIds)->whereNull('deleted_at')
            ->whereBetween('created_at', [$start, $end]);

        $dailyCheckins = (clone $bookings)->whereBetween('checked_in_at', [$start, $end])
            ->selectRaw('DATE(checked_in_at) as day, COUNT(*) as total')->groupBy('day')->pluck('total', 'day');
        $dailyCheckouts = (clone $bookings)->whereBetween('checked_out_at', [$start, $end])
            ->selectRaw('DATE(checked_out_at) as day, COUNT(*) as total')->groupBy('day')->pluck('total', 'day');
        $labels = collect(CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay()))
            ->map(fn ($date) => $date->format('d M'));
        $dateKeys = collect(CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay()))
            ->map(fn ($date) => $date->format('Y-m-d'));

        $hotelActivity = $hotels->map(function ($hotel) use ($bookings, $start, $end) {
            return [
                'name' => $hotel->name,
                'checkins' => (clone $bookings)->where('hotel_id', $hotel->id)->whereBetween('checked_in_at', [$start, $end])->count(),
                'checkouts' => (clone $bookings)->where('hotel_id', $hotel->id)->whereBetween('checked_out_at', [$start, $end])->count(),
                'players' => DB::table('players')->where('hotel_id', $hotel->id)->whereNull('deleted_at')->count(),
            ];
        })->sortByDesc('checkins')->values();

        $transactionByHotel = DB::table('menu_transactions as transactions')
            ->join('hotels', 'hotels.id', '=', 'transactions.hotel_id')
            ->whereIn('transactions.hotel_id', $hotelIds)->whereNull('transactions.deleted_at')
            ->whereBetween('transactions.created_at', [$start, $end])
            ->where('transactions.status', '!=', 'cancelled')
            ->groupBy('hotels.id', 'hotels.name')
            ->selectRaw('hotels.name, COUNT(*) as total, SUM(transactions.grand_total) as revenue')
            ->orderByDesc('total')->get();

        $topTenants = DB::table('menu_transactions as transactions')
            ->join('menu_tenants as tenants', 'tenants.id', '=', 'transactions.menu_tenant_id')
            ->join('hotels', 'hotels.id', '=', 'transactions.hotel_id')
            ->whereIn('transactions.hotel_id', $hotelIds)->whereNull('transactions.deleted_at')
            ->whereBetween('transactions.created_at', [$start, $end])
            ->where('transactions.status', '!=', 'cancelled')
            ->groupBy('tenants.id', 'tenants.name', 'hotels.name')
            ->selectRaw('tenants.name, hotels.name as hotel_name, COUNT(*) as total, SUM(transactions.grand_total) as revenue')
            ->orderByDesc('total')->limit(5)->get();

        return view('pages.manager.dashboard', [
            'page' => 'manager-dashboard',
            'icon' => 'fa fa-chart-line',
            'dateRange' => $start->format('d/m/Y').' - '.$end->format('d/m/Y'),
            'totals' => [
                'hotels' => $hotels->count(),
                'active_hotels' => $hotels->where('is_active', true)->count(),
                'players' => DB::table('players')->whereIn('hotel_id', $hotelIds)->whereNull('deleted_at')->count(),
                'active_guests' => (clone $bookings)->whereNotNull('checked_in_at')->whereNull('checked_out_at')->count(),
                'checkins' => (clone $bookings)->whereBetween('checked_in_at', [$start, $end])->count(),
                'checkouts' => (clone $bookings)->whereBetween('checked_out_at', [$start, $end])->count(),
                'transactions' => (clone $transactions)->where('status', '!=', 'cancelled')->count(),
                'revenue' => (float) (clone $transactions)->where('status', '!=', 'cancelled')->sum('grand_total'),
            ],
            'bookingChart' => [
                'labels' => $labels,
                'checkins' => $dateKeys->map(fn ($day) => (int) ($dailyCheckins[$day] ?? 0)),
                'checkouts' => $dateKeys->map(fn ($day) => (int) ($dailyCheckouts[$day] ?? 0)),
            ],
            'hotelActivity' => $hotelActivity,
            'transactionByHotel' => $transactionByHotel,
            'topTenants' => $topTenants,
        ]);
    }

    private function dateRange(Request $request): array
    {
        $defaultStart = now()->subDays(29)->startOfDay();
        $defaultEnd = now()->endOfDay();
        $parts = preg_split('/\s+-\s+/', (string) $request->input('daterange'));

        try {
            $start = isset($parts[0]) && $parts[0] !== '' ? Carbon::createFromFormat('d/m/Y', $parts[0])->startOfDay() : $defaultStart;
            $end = isset($parts[1]) ? Carbon::createFromFormat('d/m/Y', $parts[1])->endOfDay() : $defaultEnd;
        } catch (\Throwable) {
            return [$defaultStart, $defaultEnd];
        }

        return $start->lte($end) ? [$start, $end] : [$defaultStart, $defaultEnd];
    }
}
