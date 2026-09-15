<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ManagerPortfolioController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->user()->managedHotels()
            ->wherePivot('is_active', true)
            ->where('hotels.is_system', false)
            ->with(['latestLicense'])
            ->withCount([
                'users',
                'players',
                'bookings as checkins_count' => fn ($booking) => $booking->whereNotNull('checked_in_at'),
                'bookings as checkouts_count' => fn ($booking) => $booking->whereNotNull('checked_out_at'),
            ])
            ->orderBy('hotels.name');

        if ($request->ajax()) {
            return DataTables::of($query)
                ->editColumn('address', fn (Hotel $hotel) => $hotel->address ?: '-')
                ->addColumn('hotel_status', fn (Hotel $hotel) => sprintf(
                    '<span class="badge badge-%s">%s</span>',
                    $hotel->is_active ? 'success' : 'secondary',
                    $hotel->is_active ? 'Aktif' : 'Nonaktif'
                ))
                ->addColumn('license_status', fn (Hotel $hotel) => $this->licenseBadge($hotel))
                ->addColumn('license_expires_at', fn (Hotel $hotel) => $hotel->latestLicense?->expires_at?->format('d/m/Y') ?? ($hotel->latestLicense ? 'Tidak terbatas' : '-'))
                ->addColumn('action', fn (Hotel $hotel) => view('pages.manager.hotel-action', compact('hotel'))->render())
                ->rawColumns(['hotel_status', 'license_status', 'action'])
                ->make(true);
        }

        return view('pages.manager.portfolio', [
            'page' => 'manager-portfolio',
            'icon' => 'fa fa-building',
        ]);
    }

    private function licenseBadge(Hotel $hotel): string
    {
        $license = $hotel->latestLicense;
        if (! $license || ! $license->isUsable()) {
            return '<span class="badge badge-danger">'.($license ? ucfirst($license->status) : 'Belum ada').'</span>';
        }
        if (! $license->expires_at) {
            return '<span class="badge badge-success">Aktif permanen</span>';
        }

        $days = max(0, (int) ceil(now()->startOfDay()->diffInDays($license->expires_at->startOfDay(), false)));
        $class = $days <= 7 ? 'danger' : ($days <= 30 ? 'warning' : 'success');

        return '<span class="badge badge-'.$class.'">'.$days.' hari lagi</span>';
    }
}
