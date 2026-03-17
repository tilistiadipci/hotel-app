<?php

namespace App\Http\Controllers;

use App\Repositories\BookingPlayerReportRepository;
use Illuminate\Http\Request;
use App\Models\Player;

class BookingPlayerReportController extends Controller
{
    private string $page = 'report-booking-players';
    private string $icon = 'fa fa-file-alt';

    public function __construct(
        private readonly BookingPlayerReportRepository $bookingPlayerReportRepository
    ) {
    }

    public function index(Request $request)
    {
        $filters = $request->only([
            'daterange',
            'player_ids',
        ]);

        $selectedPlayerIds = array_filter((array) $request->input('player_ids', []));
        $players = Player::query()
            ->select(['id', 'name', 'alias'])
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();

        return view('pages.reports.booking-players', [
            'page' => $this->page,
            'icon' => $this->icon,
            'filters' => $filters,
            'players' => $players,
            'selectedPlayerIds' => $selectedPlayerIds,
        ]);
    }

    public function data(Request $request)
    {
        $filters = $request->only([
            'daterange',
            'player_ids',
        ]);

        return $this->bookingPlayerReportRepository->getDatatable($filters);
    }

    public function export(Request $request)
    {
        $filters = $request->only([
            'daterange',
            'player_ids',
        ]);

        $offset = (int) $request->input('offset', 0);
        $limit = (int) $request->input('limit', 500);
        $limit = max(1, min($limit, 2000));

        return response()->json(
            $this->bookingPlayerReportRepository->getChunk($filters, $offset, $limit)
        );
    }
}
