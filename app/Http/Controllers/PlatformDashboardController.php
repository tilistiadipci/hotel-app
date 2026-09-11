<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\HotelVisitLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlatformDashboardController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->date('from')?->startOfDay() ?? now()->subDays(29)->startOfDay();
        $until = $request->date('until')?->endOfDay() ?? now()->endOfDay();

        $visits = HotelVisitLog::query()->whereBetween('visited_at', [$from, $until]);
        $uniqueVisitors = (clone $visits)->distinct()->count('ip_address');
        $totalVisits = (clone $visits)->count();
        $hotelCount = Hotel::query()->count();
        $activeHotelCount = Hotel::query()->where('is_active', true)->where('status', 'active')->count();
        $adminCount = User::query()->withoutGlobalScope('hotel')
            ->whereHas('role', fn ($query) => $query->where('category', 'admin'))->count();
        $totalUserCount = User::query()->withoutGlobalScope('hotel')->whereNotNull('hotel_id')->count();
        $totalLoginCount = (int) User::query()->withoutGlobalScope('hotel')->whereNotNull('hotel_id')->sum('login_count');
        $neverLoggedInAdminCount = User::query()->withoutGlobalScope('hotel')
            ->whereHas('role', fn ($query) => $query->where('category', 'admin'))
            ->whereNull('last_login_at')->count();
        $totalTenantCount = DB::table('menu_tenants')->whereNull('deleted_at')->count();
        $totalPlayerCount = DB::table('players')->whereNull('deleted_at')->count();

        $recentAdminLogins = User::query()->withoutGlobalScope('hotel')
            ->with(['hotel', 'profile'])
            ->whereHas('role', fn ($query) => $query->where('category', 'admin'))
            ->whereNotNull('last_login_at')
            ->orderByDesc('last_login_at')
            ->limit(10)
            ->get();

        $visitsByIp = (clone $visits)
            ->select('ip_address', DB::raw('COUNT(*) as total'), DB::raw('MAX(visited_at) as last_visit'))
            ->groupBy('ip_address')->orderByDesc('total')->limit(20)->get();

        $visitsByHotel = Hotel::query()
            ->with('latestLicense')
            ->withSum('users', 'login_count')
            ->withCount([
                'users',
                'menuTenants',
                'players',
                'visits' => fn ($query) => $query->whereBetween('visited_at', [$from, $until]),
            ])
            ->orderByDesc('visits_count')
            ->orderBy('name')
            ->get();

        return view('pages.platform.dashboard', compact(
            'hotelCount', 'activeHotelCount', 'adminCount', 'totalUserCount', 'totalTenantCount',
            'totalPlayerCount', 'totalLoginCount', 'neverLoggedInAdminCount', 'uniqueVisitors', 'totalVisits',
            'recentAdminLogins', 'visitsByIp', 'visitsByHotel', 'from', 'until'
        ) + ['page' => 'platform-dashboard', 'icon' => 'fa fa-chart-line']);
    }
}
