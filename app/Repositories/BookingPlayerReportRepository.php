<?php

namespace App\Repositories;

use App\Models\Booking;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class BookingPlayerReportRepository extends BaseRepository
{
    public function __construct(Booking $booking)
    {
        parent::__construct($booking);
    }

    public function getReport(array $filters = [])
    {
        return $this->baseQuery($filters)
            ->orderByDesc('checked_in_at')
            ->get();
    }

    public function getDatatable(array $filters = [])
    {
        $query = $this->baseQuery($filters);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('player_name', function ($row) {
                return $row->player?->name ?? '-';
            })
            ->addColumn('player_alias', function ($row) {
                return $row->player?->alias ?? '-';
            })
            ->addColumn('guest_name', function ($row) {
                return $row->guest_name ?? '-';
            })
            ->addColumn('checked_in_at', function ($row) {
                return optional($row->checked_in_at)->format('d/m/Y H:i') ?? '-';
            })
            ->addColumn('checked_out_at', function ($row) {
                return optional($row->checked_out_at)->format('d/m/Y H:i') ?? '-';
            })
            ->make(true);
    }

    public function getChunk(array $filters, int $offset, int $limit): array
    {
        $query = $this->baseQuery($filters);
        $total = $query->count();

        $rows = $query
            ->orderByDesc('checked_in_at')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                return [
                    $row->player?->name ?? '-',
                    $row->player?->alias ?? '-',
                    $row->guest_name ?? '-',
                    optional($row->checked_in_at)->format('d/m/Y H:i') ?? '-',
                    optional($row->checked_out_at)->format('d/m/Y H:i') ?? '-',
                ];
            })
            ->values()
            ->all();

        $nextOffset = $offset + count($rows);

        return [
            'total' => $total,
            'rows' => $rows,
            'next_offset' => $nextOffset,
            'has_more' => $nextOffset < $total,
        ];
    }

    private function baseQuery(array $filters)
    {
        $query = $this->query()
            ->with(['player'])
            ->whereNotNull('checked_in_at');

        $playerIds = $filters['player_ids'] ?? [];
        if (!is_array($playerIds)) {
            $playerIds = array_filter(explode(',', (string) $playerIds));
        }
        if (!empty($playerIds)) {
            $query->whereIn('player_id', $playerIds);
        }

        $dateRange = trim((string) ($filters['daterange'] ?? ''));
        if ($dateRange !== '') {
            [$startDate, $endDate] = $this->parseDateRange($dateRange);
            if ($startDate && $endDate) {
                $query->whereBetween('checked_in_at', [$startDate, $endDate]);
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
}
