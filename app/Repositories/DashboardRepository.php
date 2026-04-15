<?php

namespace App\Repositories;

use App\Models\Booking;
use App\Models\MenuTransaction;
use App\Models\Player;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
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

    public function resolveDateRange(?string $dateRange = null): array
    {
        $today = Carbon::today();
        $startDate = $today->copy()->startOfDay();
        $endDate = $today->copy()->endOfDay();

        $value = trim((string) $dateRange);
        if ($value !== '') {
            $parts = array_map('trim', explode(' - ', $value));

            if (count($parts) === 1) {
                $parts[1] = $parts[0];
            }

            if (count($parts) >= 2) {
                try {
                    $startDate = Carbon::createFromFormat('d/m/Y', $parts[0])->startOfDay();
                    $endDate = Carbon::createFromFormat('d/m/Y', $parts[1])->endOfDay();
                } catch (\Exception $e) {
                    $startDate = $today->copy()->startOfDay();
                    $endDate = $today->copy()->endOfDay();
                }
            }
        }

        if ($startDate->greaterThan($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        return [$startDate, $endDate, $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y')];
    }

    public function pantryTransactionCount(Carbon $startDate, Carbon $endDate): int
    {
        return MenuTransaction::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    public function pantryTransactionCountToday(): int
    {
        [$startDate, $endDate] = $this->resolveDateRange(today()->format('d/m/Y'));

        return $this->pantryTransactionCount($startDate, $endDate);
    }

    public function bookingCheckinCount(Carbon $startDate, Carbon $endDate): int
    {
        return Booking::query()
            ->whereBetween('checked_in_at', [$startDate, $endDate])
            ->count();
    }

    public function bookingCheckinCountToday(): int
    {
        [$startDate, $endDate] = $this->resolveDateRange(today()->format('d/m/Y'));

        return $this->bookingCheckinCount($startDate, $endDate);
    }

    public function bookingCheckoutCount(Carbon $startDate, Carbon $endDate): int
    {
        return Booking::query()
            ->whereNotNull('checked_out_at')
            ->whereBetween('checked_out_at', [$startDate, $endDate])
            ->count();
    }

    public function bookingCheckoutCountToday(): int
    {
        [$startDate, $endDate] = $this->resolveDateRange(today()->format('d/m/Y'));

        return $this->bookingCheckoutCount($startDate, $endDate);
    }

    public function checkinPlayerDonutChart(Carbon $startDate, Carbon $endDate): array
    {
        $result = Booking::query()
            ->join('players', 'players.id', '=', 'bookings.player_id')
            ->selectRaw('COALESCE(NULLIF(players.alias, ""), players.name) as player_label, COUNT(*) as total')
            ->whereBetween('bookings.checked_in_at', [$startDate, $endDate])
            ->groupBy('player_label')
            ->orderBy('player_label')
            ->get();

        return [
            'labels' => $result->pluck('player_label')->all(),
            'series' => $result->pluck('total')->map(fn ($total) => (int) $total)->all(),
        ];
    }

    public function checkinPlayerDonutChartToday(): array
    {
        [$startDate, $endDate] = $this->resolveDateRange(today()->format('d/m/Y'));

        return $this->checkinPlayerDonutChart($startDate, $endDate);
    }

    public function pantryTransactionDailyActivityChart(Carbon $startDate, Carbon $endDate): array
    {
        [$dateKeys, $dateLabels] = $this->buildDateAxis($startDate, $endDate);

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
            ->selectRaw('status, DATE(created_at) as date_key, COUNT(*) as total')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('status', 'date_key')
            ->get();

        return [
            'labels' => $dateLabels,
            'series' => collect($statuses)->map(function ($config, $status) use ($dateKeys, $result) {
                return [
                    'name' => $config['label'],
                    'data' => collect($dateKeys)->map(function ($dateKey) use ($result, $status) {
                        return (int) optional(
                            $result->first(fn ($item) => $item->status === $status && $item->date_key === $dateKey)
                        )->total ?: 0;
                    })->all(),
                    'color' => $config['color'],
                ];
            })->values()->all(),
        ];
    }

    public function pantryTransactionDailyActivityChartToday(): array
    {
        [$startDate, $endDate] = $this->resolveDateRange(today()->format('d/m/Y'));

        return $this->pantryTransactionDailyActivityChart($startDate, $endDate);
    }

    public function bookingDailyActivityChart(Carbon $startDate, Carbon $endDate): array
    {
        [$dateKeys, $dateLabels] = $this->buildDateAxis($startDate, $endDate);

        $checkinResult = Booking::query()
            ->selectRaw('DATE(checked_in_at) as date_key, COUNT(*) as total')
            ->whereBetween('checked_in_at', [$startDate, $endDate])
            ->groupBy('date_key')
            ->pluck('total', 'date_key');

        $checkoutResult = Booking::query()
            ->selectRaw('DATE(checked_out_at) as date_key, COUNT(*) as total')
            ->whereNotNull('checked_out_at')
            ->whereBetween('checked_out_at', [$startDate, $endDate])
            ->groupBy('date_key')
            ->pluck('total', 'date_key');

        return [
            'labels' => $dateLabels,
            'series' => [
                [
                    'name' => trans('common.dashboard.checkin_chart_label'),
                    'data' => collect($dateKeys)->map(fn ($dateKey) => (int) ($checkinResult[$dateKey] ?? 0))->all(),
                    'color' => '#10b981',
                ],
                [
                    'name' => trans('common.dashboard.checkout_chart_label'),
                    'data' => collect($dateKeys)->map(fn ($dateKey) => (int) ($checkoutResult[$dateKey] ?? 0))->all(),
                    'color' => '#a855f7',
                ],
            ],
        ];
    }

    private function buildDateAxis(Carbon $startDate, Carbon $endDate): array
    {
        $dateKeys = [];
        $labels = [];

        $period = CarbonPeriod::create(
            $startDate->copy()->startOfDay(),
            $endDate->copy()->startOfDay()
        );

        foreach ($period as $date) {
            $dateKeys[] = $date->format('Y-m-d');
            $labels[] = $date->format('d/m');
        }

        return [$dateKeys, $labels];
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
