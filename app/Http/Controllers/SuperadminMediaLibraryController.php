<?php

namespace App\Http\Controllers;

use App\Models\Hotel;

class SuperadminMediaLibraryController extends Controller
{
    public function index()
    {
        $hotels = Hotel::query()
            ->where('is_active', true)
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_system']);

        $activeHotelId = session('active_hotel_id') ?: Hotel::masterId();

        return view('pages.platform.media_library.index', [
            'page' => 'media-library',
            'icon' => 'fa fa-photo-video',
            'hotels' => $hotels,
            'activeHotelId' => $activeHotelId,
        ]);
    }
}
