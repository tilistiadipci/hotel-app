<?php

namespace App\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardRepository
{
    public function userCount()
    {
        return app(UserRepository::class)->countNotAdmin();
    }

    public function baseQueryChartAudit()
    {
        $year = request('daterange') ?? date('Y');
        $startDate = Carbon::createFromFormat('Y', $year)->startOfYear()->format('Y-m-d H:i:s');
        $endDate = Carbon::createFromFormat('Y', $year)->endOfYear()->format('Y-m-d H:i:s');

        $query = DB::table('audit_reports')
                ->whereBetween('audit_date', [$startDate, $endDate]);

        return $query;
    }

    public function getAuditChartLabels($returnValue = 'keys')
    {
        $labels = [
            trans('common.list_month.january') => 'January',
            trans('common.list_month.february') => 'February',
            trans('common.list_month.march') => 'March',
            trans('common.list_month.april') => 'April',
            trans('common.list_month.may') => 'May',
            trans('common.list_month.june') => 'June',
            trans('common.list_month.july') => 'July',
            trans('common.list_month.august') => 'August',
            trans('common.list_month.september') => 'September',
            trans('common.list_month.october') => 'October',
            trans('common.list_month.november') => 'November',
            trans('common.list_month.december') => 'December',
        ];

        if ($returnValue == 'keys') {
            return array_keys($labels);
        }

        if ($returnValue == 'values') {
            return array_values($labels);
        }
    }


    public function getAuditChartSeries()
    {
        $query = $this->baseQueryChartAudit();
        $result = $query->selectRaw('
            DATE_FORMAT(audit_date, "%M") as month_text,
            DATE_FORMAT(audit_date, "%m") as month,
            SUM(found) as found,
            SUM(not_found) as not_found,
            SUM(wrong_location) as wrong_location
        ')
        ->groupBy('month', 'month_text')
        ->get();

        $months = $this->getAuditChartLabels(returnValue: 'values');
        $dataFound = [];
        $dataNotFound = [];
        $dataWrongLocation = [];

        foreach ($months as $month) {
            if ($result->count() == 0) {
                $dataFound[] = 0;
                $dataNotFound[] = 0;
                $dataWrongLocation[] = 0;
            } else {
                foreach ($result as $item) {
                    if ($month == $item->month_text) {
                        $dataFound[] = (int) $item->found;
                        $dataNotFound[] = (int) $item->not_found;
                        $dataWrongLocation[] = (int) $item->wrong_location;
                    } else {
                        $dataFound[] = 0;
                        $dataNotFound[] = 0;
                        $dataWrongLocation[] = 0;
                    }
                }
            }
        }

        $data = [
            [
                'name' => trans('common.audit.found'),
                'data' => $dataFound,
                'color' => '#16aaff'
            ],
            [
                'name' => trans('common.audit.not_found'),
                'data' => $dataNotFound,
                'color' => '#d9534f'
            ],
            [
                'name' => trans('common.audit.wrong_location'),
                'data' => $dataWrongLocation,
                'color' => '#f0ad4e'
            ],
        ];

        return $data;
    }
}
