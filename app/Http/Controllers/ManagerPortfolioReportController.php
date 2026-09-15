<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Repositories\BookingPlayerDurationReportRepository;
use App\Repositories\BookingPlayerReportRepository;
use Illuminate\Http\Request;

class ManagerPortfolioReportController extends Controller
{
    public function __construct(
        private readonly BookingPlayerReportRepository $bookingReportRepository,
        private readonly BookingPlayerDurationReportRepository $durationReportRepository
    ) {}

    public function checkins(Request $request)
    {
        return $this->reportView($request, 'checkins');
    }

    public function checkinsData(Request $request)
    {
        return $this->bookingReportRepository->getDatatable($this->filters($request));
    }

    public function checkinsExport(Request $request)
    {
        return response()->json($this->bookingReportRepository->getChunk(
            $this->filters($request),
            (int) $request->input('offset', 0),
            $this->exportLimit($request)
        ));
    }

    public function playerUsage(Request $request)
    {
        return $this->reportView($request, 'player-usage');
    }

    public function playerUsageData(Request $request)
    {
        return $this->durationReportRepository->getDatatable($this->filters($request));
    }

    public function playerUsageExport(Request $request)
    {
        return response()->json($this->durationReportRepository->getChunk(
            $this->filters($request),
            (int) $request->input('offset', 0),
            $this->exportLimit($request)
        ));
    }

    public function playerUsageChart(Request $request)
    {
        return response()->json($this->durationReportRepository->getChart($this->filters($request)));
    }

    private function reportView(Request $request, string $type)
    {
        $hotelIds = $this->managedHotelIds($request);
        $players = Player::query()
            ->withoutGlobalScope('hotel')
            ->with('hotel:id,name')
            ->select(['id', 'hotel_id', 'name', 'alias'])
            ->whereIn('hotel_id', $hotelIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $viewData = [
            'managerPortfolio' => true,
            'filters' => $request->only(['daterange', 'player_ids']),
            'players' => $players,
            'selectedPlayerIds' => array_map('intval', array_filter((array) $request->input('player_ids', []))),
        ];

        if ($type === 'checkins') {
            return view('pages.reports.booking-players', $viewData + [
                'page' => 'manager-report-checkins',
                'icon' => 'fa fa-file-alt',
            ]);
        }

        return view('pages.reports.player-durations', $viewData + [
            'page' => 'manager-report-player-usage',
            'icon' => 'fa fa-chart-bar',
        ]);
    }

    private function filters(Request $request): array
    {
        return [
            'daterange' => $request->input('daterange'),
            'player_ids' => $request->input('player_ids', []),
            'hotel_ids' => $this->managedHotelIds($request),
            'include_hotel' => true,
        ];
    }

    private function managedHotelIds(Request $request): array
    {
        return $request->user()->managedHotels()
            ->wherePivot('is_active', true)
            ->where('hotels.is_system', false)
            ->pluck('hotels.id')
            ->all();
    }

    private function exportLimit(Request $request): int
    {
        return max(1, min((int) $request->input('limit', 500), 2000));
    }
}
