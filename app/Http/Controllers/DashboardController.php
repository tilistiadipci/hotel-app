<?php

namespace App\Http\Controllers;

use App\Repositories\DashboardRepository;
class DashboardController extends Controller
{
    protected $dashboardRepository;

    public function __construct(
        DashboardRepository $dashboardRepository
    ) {
        $this->dashboardRepository = $dashboardRepository;
    }

    public function index()
    {
        $data['page'] = 'dashboard';
        $data['tabActive'] = 'dashboard';
        $data['title'] = 'Dashboard';
        $data['showDateFilter'] = false;
        $data['playerCount'] = $this->dashboardRepository->playerCount();
        $data['pantryTransactionCount'] = $this->dashboardRepository->pantryTransactionCountToday();
        // $data['bookingCheckinCount'] = $this->dashboardRepository->bookingCheckinCountToday();
        $data['bookingCheckoutCount'] = $this->dashboardRepository->bookingCheckoutCountToday();
        $data['transactionDonutChart'] = $this->dashboardRepository->checkinPlayerDonutChart();
        $data['bookingActivityChart'] = $this->dashboardRepository->pantryTransactionDailyActivityChart();

        return view('pages.dashboard.index', $data);
    }

    public function report()
    {
        $data['page'] = 'dashboard';
        $data['tabActive'] = 'report';
        $data['title'] = 'Dashboard Report';

        return view('pages.dashboard.report', $data);
    }

    public function getAuditChart()
    {
        try {
            $labels = $this->dashboardRepository->getAuditChartLabels();
            $series = $this->dashboardRepository->getAuditChartSeries();

            return response()->json([
                'labels' => $labels,
                'series' => $series,
            ]);
        } catch (\Exception $e) {
            return $this->debugError($e);
        }
    }
}
