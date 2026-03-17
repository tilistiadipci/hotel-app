<?php

namespace App\Repositories;

use App\Models\MenuTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class MenuTransactionReportRepository extends BaseRepository
{
    public function __construct(MenuTransaction $menuTransaction)
    {
        parent::__construct($menuTransaction);
    }

    public function getDatatable(array $filters = [])
    {
        $query = $this->baseQuery($filters);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('created_at', function ($row) {
                return optional($row->created_at)->format('d/m/Y H:i') ?? '-';
            })
            ->addColumn('guest_name', function ($row) {
                return $row->guest_name ?? '-';
            })
            ->addColumn('player_alias', function ($row) {
                return $row->player_alias ?? '-';
            })
            ->addColumn('total_items', function ($row) {
                return (int) ($row->total_items ?? 0);
            })
            ->addColumn('grand_total', function ($row) {
                return number_format((float) ($row->grand_total ?? 0), 0);
            })
            ->addColumn('payment_status', function ($row) {
                return $row->payment_status ? strtoupper($row->payment_status) : '-';
            })
            ->addColumn('payment_method', function ($row) {
                return $row->payment_method ? strtoupper($row->payment_method) : '-';
            })
            ->addColumn('processed_by', function ($row) {
                return $row->processed_by_name ?? '-';
            })
            ->addColumn('completed_by', function ($row) {
                return $row->completed_by_name ?? '-';
            })
            ->make(true);
    }

    public function getChunk(array $filters, int $offset, int $limit): array
    {
        $baseQuery = $this->baseQuery($filters);
        $total = DB::query()->fromSub($baseQuery, 'menu_tx_report')->count();

        $rows = (clone $baseQuery)
            ->orderByDesc('menu_transactions.created_at')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                return [
                    optional($row->created_at)->format('d/m/Y H:i') ?? '-',
                    $row->guest_name ?? '-',
                    $row->player_alias ?? '-',
                    (int) ($row->total_items ?? 0),
                    number_format((float) ($row->grand_total ?? 0), 0),
                    $row->payment_status ? strtoupper($row->payment_status) : '-',
                    $row->payment_method ? strtoupper($row->payment_method) : '-',
                    $row->processed_by_name ?? '-',
                    $row->completed_by_name ?? '-',
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

    private function baseQuery(array $filters)
    {
        $query = $this->query()
            ->leftJoin('menu_transaction_details', 'menu_transaction_details.menu_transaction_id', '=', 'menu_transactions.id')
            ->join('players', 'players.id', '=', 'menu_transactions.player_id')
            ->leftJoin('users as processed_users', 'processed_users.id', '=', 'menu_transactions.processed_by')
            ->leftJoin('users as completed_users', 'completed_users.id', '=', 'menu_transactions.completed_by')
            ->select([
                'menu_transactions.id',
                'menu_transactions.created_at',
                'menu_transactions.guest_name',
                'menu_transactions.payment_status',
                'menu_transactions.payment_method',
                'menu_transactions.grand_total',
                'players.alias as player_alias',
                'processed_users.username as processed_by_name',
                'completed_users.username as completed_by_name',
                DB::raw('COALESCE(SUM(menu_transaction_details.quantity), 0) as total_items'),
            ])
            ->groupBy(
                'menu_transactions.id',
                'menu_transactions.created_at',
                'menu_transactions.guest_name',
                'menu_transactions.payment_status',
                'menu_transactions.payment_method',
                'menu_transactions.grand_total',
                'players.alias',
                'processed_users.username',
                'completed_users.username'
            );

        $playerIds = $filters['player_ids'] ?? [];
        if (!is_array($playerIds)) {
            $playerIds = array_filter(explode(',', (string) $playerIds));
        }
        if (!empty($playerIds)) {
            $query->whereIn('menu_transactions.player_id', $playerIds);
        }

        $dateRange = trim((string) ($filters['daterange'] ?? ''));
        if ($dateRange !== '') {
            [$startDate, $endDate] = $this->parseDateRange($dateRange);
            if ($startDate && $endDate) {
                $query->whereBetween('menu_transactions.created_at', [$startDate, $endDate]);
            }
        }

        $paymentStatus = $filters['payment_status'] ?? null;
        if ($paymentStatus) {
            $query->where('menu_transactions.payment_status', $paymentStatus);
        }

        $paymentMethod = $filters['payment_method'] ?? null;
        if ($paymentMethod) {
            $query->where('menu_transactions.payment_method', $paymentMethod);
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
