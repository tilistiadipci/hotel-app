<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\ManagerLicense;
use App\Models\Player;
use App\Models\PlayerLicense;
use App\Services\LicenseAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ManagerLicenseController extends Controller
{
    public function index(Request $request)
    {
        $hotelIds = $request->user()->managedHotels()
            ->wherePivot('is_active', true)
            ->where('hotels.is_system', false)
            ->pluck('hotels.id');

        $licenses = ManagerLicense::query()
            ->where('manager_id', $request->user()->id)
            ->with('masterPaket')
            ->withSum(['usages as used_players' => fn ($query) => $query->where('status', PlayerLicense::STATUS_ACTIVE)], 'quantity')
            ->latest()
            ->get();

        $assignments = PlayerLicense::query()
            ->whereHas('managerLicense', fn ($query) => $query->where('manager_id', $request->user()->id))
            ->with(['managerLicense.masterPaket', 'hotel', 'player'])
            ->latest()
            ->get();

        return view('pages.manager.licenses.index', [
            'page' => 'manager-licenses',
            'icon' => 'fa fa-key',
            'licenses' => $licenses,
            'assignments' => $assignments,
            'hotels' => Hotel::query()->whereIn('id', $hotelIds)->orderBy('name')->get(),
            'players' => Player::query()->withoutGlobalScope('hotel')
                ->whereIn('hotel_id', $hotelIds)
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request, LicenseAssignmentService $service)
    {
        $data = $this->validateData($request);
        $license = ManagerLicense::query()
            ->where('manager_id', $request->user()->id)
            ->with('masterPaket')
            ->findOrFail($data['manager_license_id']);

        $hotel = $request->user()->managedHotels()
            ->wherePivot('is_active', true)
            ->where('hotels.id', $data['hotel_id'])
            ->where('hotels.is_system', false)
            ->firstOrFail();

        $playerId = $data['player_id'] ?? null;
        if ($playerId) {
            Player::query()->withoutGlobalScope('hotel')
                ->where('hotel_id', $hotel->id)
                ->whereKey($playerId)
                ->firstOrFail();

            if (PlayerLicense::query()->where('player_id', $playerId)->where('status', PlayerLicense::STATUS_ACTIVE)->exists()) {
                return back()->withErrors(['player_id' => 'Player ini sudah memiliki license aktif.'])->withInput();
            }
        }

        $quantity = $playerId ? 1 : (int) $data['quantity'];
        $service->assertManagerHasCapacity($license, $quantity);

        DB::transaction(function () use ($data, $request, $license, $hotel, $playerId, $quantity, $service): void {
            $assignment = PlayerLicense::query()->create([
                'manager_license_id' => $license->id,
                'hotel_id' => $hotel->id,
                'player_id' => $playerId,
                'quantity' => $quantity,
                'status' => PlayerLicense::STATUS_ACTIVE,
                'starts_at' => $license->starts_at,
                'expires_at' => $license->expires_at,
                'assigned_by' => $request->user()->id,
                'notes' => $data['notes'] ?? null,
            ]);

            $service->syncHotelLicense($hotel, $assignment->managerLicense);
        });

        return redirect()->route('manager.licenses.index')->with('success', 'License berhasil di-assign.');
    }

    public function destroy(Request $request, PlayerLicense $assignment, LicenseAssignmentService $service)
    {
        abort_unless($assignment->managerLicense()->where('manager_id', $request->user()->id)->exists(), 403);

        $hotel = $assignment->hotel;
        $license = $assignment->managerLicense;

        DB::transaction(function () use ($assignment, $hotel, $license, $service): void {
            $assignment->delete();
            if ($hotel) {
                $service->syncHotelLicense($hotel, $license);
            }
        });

        return redirect()->route('manager.licenses.index')->with('success', 'Assignment license berhasil dihapus.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'manager_license_id' => ['required', 'integer', Rule::exists('manager_licenses', 'id')],
            'hotel_id' => ['required', 'uuid', Rule::exists('hotels', 'id')->whereNull('deleted_at')],
            'assign_to' => ['required', Rule::in(['hotel', 'player'])],
            'player_id' => ['nullable', 'required_if:assign_to,player', 'integer', Rule::exists('players', 'id')->whereNull('deleted_at')],
            'quantity' => ['nullable', 'required_if:assign_to,hotel', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
