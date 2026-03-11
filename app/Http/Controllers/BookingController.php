<?php

namespace App\Http\Controllers;

use App\Repositories\BookingRepository;
use App\Repositories\PlayerRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    private string $page = 'booking';
    private string $icon = 'fa fa-calendar';

    public function __construct(
        private readonly BookingRepository $bookingRepository,
        private readonly PlayerRepository $playerRepository
    ) {
    }

    public function index(Request $request)
    {
        $filters = $request->only([
            'guest_name',
            'player_name',
            'room_name',
            'status',
        ]);

        return view('pages.booking.index', [
            'page' => $this->page,
            'icon' => $this->icon,
            'players' => $this->bookingRepository->getPlayerOverview($filters),
            'filters' => $filters,
        ]);
    }

    public function store(Request $request, string $playerUuid)
    {
        $validated = $request->validate([
            'guest_name' => ['required', 'string', 'max:150'],
        ]);

        try {
            $response = DB::transaction(function () use ($playerUuid, $validated) {
                $player = $this->playerRepository->findUidForUpdate($playerUuid);
                if (!$player) {
                    return response()->json([
                        'status' => false,
                        'message' => trans('common.error.404'),
                    ], 404);
                }

                if ($this->bookingRepository->findActiveByPlayerIdForUpdate($player->id)) {
                    return response()->json([
                        'status' => false,
                        'message' => trans('common.booking.already_booked'),
                    ], 422);
                }

                $this->bookingRepository->createForPlayer($player, $validated['guest_name']);

                return response()->json([
                    'status' => true,
                    'message' => trans('common.success.create'),
                ]);
            });

            return $response;
        } catch (\Exception $e) {
            return $this->debugErrorResJson($e);
        }
    }

    public function checkout(string $playerUuid)
    {
        try {
            $response = DB::transaction(function () use ($playerUuid) {
                $player = $this->playerRepository->findUidForUpdate($playerUuid);
                if (!$player) {
                    return response()->json([
                        'status' => false,
                        'message' => trans('common.error.404'),
                    ], 404);
                }

                $booking = $this->bookingRepository->findActiveByPlayerIdForUpdate($player->id);
                if (!$booking) {
                    return response()->json([
                        'status' => false,
                        'message' => trans('common.booking.no_active_booking'),
                    ], 422);
                }

                $this->bookingRepository->checkout($booking);

                return response()->json([
                    'status' => true,
                    'message' => trans('common.success.update'),
                ]);
            });

            return $response;
        } catch (\Exception $e) {
            return $this->debugErrorResJson($e);
        }
    }
}
