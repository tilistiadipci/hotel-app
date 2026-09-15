<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\TvChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ManagerTvChannelAccessController extends Controller
{
    public function index(Request $request)
    {
        $hotels = $this->managedHotels($request)->get(['hotels.id', 'hotels.name', 'hotels.code']);
        $selectedHotel = $request->filled('hotel_id')
            ? $this->findManagedHotel($request, (string) $request->input('hotel_id'))
            : $hotels->first();

        $channels = collect();
        $assignedChannelIds = [];

        if ($selectedHotel && ($masterId = Hotel::masterId())) {
            $assignments = DB::table('hotel_tv_channel')
                ->where('hotel_id', $selectedHotel->id)
                ->get()
                ->keyBy(fn ($assignment) => (int) $assignment->tv_channel_id);

            $channels = TvChannel::query()
                ->withoutGlobalScope('hotel')
                ->with('imageMedia')
                ->where('hotel_id', $masterId)
                ->whereIn('id', $assignments->keys())
                ->whereNull('deleted_at')
                ->orderBy('group_title')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            $assignedChannelIds = $assignments
                ->filter(fn ($assignment) => (bool) $assignment->is_active)
                ->keys()
                ->all();
        }

        return view('pages.manager.tv-channel-access', [
            'page' => 'manager-tv-channel-access',
            'icon' => 'fa fa-tv',
            'hotels' => $hotels,
            'selectedHotel' => $selectedHotel,
            'channels' => $channels,
            'assignedChannelIds' => $assignedChannelIds,
        ]);
    }

    public function update(Request $request, Hotel $hotel)
    {
        $hotel = $this->findManagedHotel($request, $hotel->id);
        $masterId = Hotel::masterId();
        abort_unless($masterId, 422);

        $superadminChannelIds = DB::table('hotel_tv_channel')
            ->join('tv_channels', 'tv_channels.id', '=', 'hotel_tv_channel.tv_channel_id')
            ->where('hotel_tv_channel.hotel_id', $hotel->id)
            ->where('tv_channels.hotel_id', $masterId)
            ->where('tv_channels.is_active', true)
            ->whereNull('tv_channels.deleted_at')
            ->pluck('tv_channels.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $data = $request->validate([
            'channel_ids' => ['nullable', 'array'],
            'channel_ids.*' => [
                'integer',
                'distinct',
                Rule::in($superadminChannelIds),
            ],
        ]);

        $selectedIds = array_map('intval', $data['channel_ids'] ?? []);

        DB::transaction(function () use ($hotel, $superadminChannelIds, $selectedIds): void {
            foreach ($superadminChannelIds as $channelId) {
                DB::table('hotel_tv_channel')
                    ->where('hotel_id', $hotel->id)
                    ->where('tv_channel_id', $channelId)
                    ->update([
                        'is_active' => in_array($channelId, $selectedIds, true),
                        'updated_at' => now(),
                    ]);
            }
        });

        return redirect()->route('manager.tv-channels.index', ['hotel_id' => $hotel->id])
            ->with('success', 'Akses TV channel untuk '.$hotel->name.' berhasil disimpan.');
    }

    private function managedHotels(Request $request)
    {
        return $request->user()->managedHotels()
            ->wherePivot('is_active', true)
            ->where('hotels.is_system', false)
            ->orderBy('hotels.name');
    }

    private function findManagedHotel(Request $request, string $hotelId): Hotel
    {
        return $this->managedHotels($request)
            ->where('hotels.id', $hotelId)
            ->firstOrFail();
    }
}
