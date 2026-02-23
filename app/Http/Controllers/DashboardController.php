<?php

namespace App\Http\Controllers;

use App\Repositories\DashboardRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $data['userCount'] = $this->dashboardRepository->userCount();

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
