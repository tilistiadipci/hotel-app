<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Repositories\BookingPlayerDurationReportRepository;
use Illuminate\Http\Request;

class BookingPlayerDurationReportController extends Controller
{
    private string $page = 'report-player-durations';
    private string $icon = 'fa fa-chart-bar';

    public function __construct(
        private readonly BookingPlayerDurationReportRepository $durationReportRepository
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

        return view('pages.reports.player-durations', [
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

        return $this->durationReportRepository->getDatatable($filters);
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
            $this->durationReportRepository->getChunk($filters, $offset, $limit)
        );
    }

    public function chart(Request $request)
    {
        $filters = $request->only([
            'daterange',
            'player_ids',
        ]);

        return response()->json(
            $this->durationReportRepository->getChart($filters)
        );
    }
}
