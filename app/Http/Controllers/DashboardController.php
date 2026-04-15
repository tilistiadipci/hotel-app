<?php

namespace App\Http\Controllers;

use App\Repositories\DashboardRepository;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $dashboardRepository;

    public function __construct(
        DashboardRepository $dashboardRepository
    ) {
        $this->dashboardRepository = $dashboardRepository;
    }

    public function index(Request $request)
    {
        $settings = session('settings', []);
        $isShoppingMenuActive = ($settings['menu_shopping_status'] ?? 'active') === 'active';
        [$startDate, $endDate, $dateRangeValue] = $this->dashboardRepository->resolveDateRange(
            $request->input('daterange')
        );

        $data['page'] = 'dashboard';
        $data['tabActive'] = 'dashboard';
        $data['title'] = 'Dashboard';
        $data['showDateFilter'] = true;
        $data['dateFilterType'] = 'range';
        $data['dateFilterAction'] = url()->current();
        $data['dateFilterResetUrl'] = url()->current();
        $data['dateRangeValue'] = $dateRangeValue;
        $data['dateRangeLabel'] = $dateRangeValue;
        $data['isShoppingMenuActive'] = $isShoppingMenuActive;

        $data['playerCount'] = $this->dashboardRepository->playerCount();
        $data['pantryTransactionCount'] = $this->dashboardRepository->pantryTransactionCount($startDate, $endDate);
        $data['bookingCheckinCount'] = $this->dashboardRepository->bookingCheckinCount($startDate, $endDate);
        $data['bookingCheckoutCount'] = $this->dashboardRepository->bookingCheckoutCount($startDate, $endDate);
        $data['transactionDonutChart'] = $this->dashboardRepository->checkinPlayerDonutChart($startDate, $endDate);
        $data['bookingActivityChart'] = $isShoppingMenuActive
            ? $this->dashboardRepository->pantryTransactionDailyActivityChart($startDate, $endDate)
            : $this->dashboardRepository->bookingDailyActivityChart($startDate, $endDate);

        return view('pages.dashboard.index', $data);
    }

    public function report()
    {
        $data['page'] = 'dashboard';
        $data['tabActive'] = 'report';
        $data['title'] = 'Dashboard Report';
        $data['showDateFilter'] = false;

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
