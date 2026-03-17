<?php

namespace App\Repositories;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class BookingPlayerDurationReportRepository extends BaseRepository
{
    public function __construct(Booking $booking)
    {
        parent::__construct($booking);
    }

    public function getDatatable(array $filters = [])
    {
        $query = $this->baseQuery($filters);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('player_name', function ($row) {
                return $row->player_name ?? '-';
            })
            ->addColumn('player_alias', function ($row) {
                return $row->player_alias ?? '-';
            })
            ->addColumn('duration_human', function ($row) {
                return $this->formatDuration((int) $row->duration_minutes);
            })
            ->make(true);
    }

    public function getChunk(array $filters, int $offset, int $limit): array
    {
        $baseQuery = $this->baseQuery($filters);
        $total = DB::query()->fromSub($baseQuery, 'duration_report')->count();

        $rows = (clone $baseQuery)
            ->orderByDesc('duration_minutes')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                return [
                    $row->player_name ?? '-',
                    $row->player_alias ?? '-',
                    $this->formatDuration((int) $row->duration_minutes),
                ];
            })
            ->values()
            ->all();

        $nextOffset = $offset + count($rows);
        $hasMore = $nextOffset < $total;

        if (count($rows) === 0) {
            $hasMore = false;
        }

        return [
            'total' => $total,
            'rows' => $rows,
            'next_offset' => $nextOffset,
            'has_more' => $hasMore,
        ];
    }

    public function getChart(array $filters = []): array
    {
        $query = $this->baseQuery($filters)
            ->orderByDesc('duration_minutes');

        $rows = $query->get();

        return [
            'labels' => $rows->pluck('player_label')->all(),
            'series' => $rows->pluck('duration_minutes')->map(function ($minutes) {
                return round(((int) $minutes) / 60, 2);
            })->all(),
        ];
    }

    private function baseQuery(array $filters)
    {
        $query = $this->query()
            ->join('players', 'players.id', '=', 'bookings.player_id')
            ->whereNotNull('bookings.checked_in_at')
            ->whereNotNull('bookings.checked_out_at')
            ->select([
                'players.id as player_id',
                'players.name as player_name',
                'players.alias as player_alias',
                DB::raw('COALESCE(NULLIF(players.alias, ""), players.name) as player_label'),
                DB::raw('COALESCE(SUM(TIMESTAMPDIFF(MINUTE, bookings.checked_in_at, bookings.checked_out_at)), 0) as duration_minutes'),
            ])
            ->groupBy('players.id', 'players.name', 'players.alias');

        $playerIds = $filters['player_ids'] ?? [];
        if (!is_array($playerIds)) {
            $playerIds = array_filter(explode(',', (string) $playerIds));
        }
        if (!empty($playerIds)) {
            $query->whereIn('bookings.player_id', $playerIds);
        }

        $dateRange = trim((string) ($filters['daterange'] ?? ''));
        if ($dateRange !== '') {
            [$startDate, $endDate] = $this->parseDateRange($dateRange);
            if ($startDate && $endDate) {
                $query->whereBetween('bookings.checked_in_at', [$startDate, $endDate]);
            }
        }

        return $query;
    }

    private function parseDateRange(string $dateRange): array
    {
        $parts = array_map('trim', explode(' - ', $dateRange));

        if (count($parts) === 1) {
            $parts[1] = $parts[0];
        }

        if (count($parts) < 2) {
            return [null, null];
        }

        try {
            $startDate = Carbon::createFromFormat('d/m/Y', $parts[0])->startOfDay();
            $endDate = Carbon::createFromFormat('d/m/Y', $parts[1])->endOfDay();
        } catch (\Exception $e) {
            return [null, null];
        }

        return [$startDate, $endDate];
    }

    private function formatDuration(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        $hourLabel = $hours === 1 ? trans('common.duration.hour') : trans('common.duration.hours');
        $minuteLabel = $remainingMinutes === 1 ? trans('common.duration.minute') : trans('common.duration.minutes');

        if ($hours > 0 && $remainingMinutes > 0) {
            return "{$hours} {$hourLabel} {$remainingMinutes} {$minuteLabel}";
        }

        if ($hours > 0) {
            return "{$hours} {$hourLabel}";
        }

        return "{$remainingMinutes} {$minuteLabel}";
    }
}
