<?php

namespace App\Repositories;

use App\Models\Booking;
use App\Models\MenuTransaction;
use App\Models\Player;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardRepository
{
    public function userCount()
    {
        return app(UserRepository::class)->countNotAdmin();
    }

    public function playerCount(): int
    {
        return Player::query()
            ->where('is_active', 1)
            ->count();
    }

    public function pantryTransactionCountToday(): int
    {
        return MenuTransaction::query()
            ->whereDate('created_at', today())
            ->count();
    }

    public function bookingCheckinCountToday(): int
    {
        return Booking::query()
            ->whereDate('checked_in_at', today())
            ->count();
    }

    public function bookingCheckoutCountToday(): int
    {
        return Booking::query()
            ->whereNotNull('checked_out_at')
            ->whereDate('checked_out_at', today())
            ->count();
    }

    public function checkinPlayerDonutChart(): array
    {
        $result = Booking::query()
            ->join('players', 'players.id', '=', 'bookings.player_id')
            ->selectRaw('COALESCE(NULLIF(players.alias, ""), players.name) as player_label, COUNT(*) as total')
            ->whereDate('bookings.checked_in_at', today())
            ->groupBy('player_label')
            ->orderBy('player_label')
            ->get();

        return [
            'labels' => $result->pluck('player_label')->all(),
            'series' => $result->pluck('total')->map(fn ($total) => (int) $total)->all(),
        ];
    }

    public function pantryTransactionDailyActivityChart(): array
    {
        $hours = collect(range(0, 23))->map(function ($hour) {
            return str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':00';
        });

        $statuses = [
            'ordered' => [
                'label' => trans('common.transaction.status_label.ordered'),
                'color' => '#38bdf8',
            ],
            'processing' => [
                'label' => trans('common.transaction.status_label.processing'),
                'color' => '#f59e0b',
            ],
            'completed' => [
                'label' => trans('common.transaction.status_label.completed'),
                'color' => '#22c55e',
            ],
            'cancelled' => [
                'label' => trans('common.transaction.status_label.cancelled'),
                'color' => '#ef4444',
            ],
        ];

        $result = MenuTransaction::query()
            ->selectRaw('status, HOUR(created_at) as hour_key, COUNT(*) as total')
            ->whereDate('created_at', today())
            ->groupBy('status', 'hour_key')
            ->get();

        return [
            'labels' => $hours->all(),
            'series' => collect($statuses)->map(function ($config, $status) use ($hours, $result) {
                return [
                    'name' => $config['label'],
                    'data' => $hours->map(function ($label, $hour) use ($result, $status) {
                        return (int) optional(
                            $result->first(fn ($item) => $item->status === $status && (int) $item->hour_key === $hour)
                        )->total ?: 0;
                    })->all(),
                    'color' => $config['color'],
                ];
            })->values()->all(),
        ];
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
