<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\Player;
use App\Repositories\PlayerMqttRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class MqttSyncController extends Controller
{
    public function index(): View
    {
        return view('pages.platform.mqtt-sync.index', [
            'page' => 'mqtt-sync',
            'icon' => 'fa fa-sync-alt',
            'hotels' => Hotel::query()->excludingSystem()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'types' => array_intersect_key(
                PlayerMqttRepository::UPDATE_TYPE_LABELS,
                array_flip(PlayerMqttRepository::MANUAL_SYNC_TYPES)
            ),
        ]);
    }

    public function players(Hotel $hotel): JsonResponse
    {
        abort_if($hotel->is_system, 404);

        return response()->json([
            'players' => $hotel->players()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'serial'])
                ->map(fn (Player $player) => [
                    'id' => $player->id,
                    'name' => $player->name,
                    'serial' => $player->serial,
                ]),
        ]);
    }

    public function store(Request $request, PlayerMqttRepository $mqtt): RedirectResponse
    {
        $validated = $request->validate([
            'hotel_id' => [
                'required',
                'string',
                Rule::exists('hotels', 'id')->where(fn ($query) => $query->where('is_system', false)),
            ],
            'player_id' => ['nullable', 'integer'],
            'type' => ['required', Rule::in(PlayerMqttRepository::MANUAL_SYNC_TYPES)],
        ]);

        $hotel = Hotel::query()->with('configuration')->excludingSystem()->findOrFail($validated['hotel_id']);
        $player = null;

        if (! empty($validated['player_id'])) {
            $player = $hotel->players()->whereKey($validated['player_id'])->firstOrFail();
        }

        try {
            if ($player) {
                if ($validated['type'] === 'notification') {
                    $mqtt->publishPlayerNotification($player, $this->testNotification());
                } else {
                    $mqtt->publishPlayerUpdate($player, $validated['type']);
                }
            } else {
                if ($validated['type'] === 'notification') {
                    $mqtt->publishHotelNotification($hotel, $this->testNotification());
                } else {
                    $mqtt->publishHotelUpdate($hotel, $validated['type']);
                }
            }
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->with('error', 'MQTT gagal dikirim: '.$exception->getMessage());
        }

        $target = $player ? $player->serial : 'semua player';

        return back()->with('success', "Sync {$validated['type']} berhasil dikirim ke {$target} di {$hotel->name}.");
    }

    private function testNotification(): array
    {
        return [
            'type' => 'notification',
            'title' => 'Uji Notifikasi MQTT',
            'message' => 'Ini adalah pesan uji dari Superadmin.',
            'display' => 'popup',
            'duration' => 30,
        ];
    }
}
