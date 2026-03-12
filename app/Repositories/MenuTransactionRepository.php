<?php

namespace App\Repositories;

use App\Models\MenuTransaction;

class MenuTransactionRepository extends BaseRepository
{
    public function __construct(MenuTransaction $menuTransaction)
    {
        parent::__construct($menuTransaction);
    }

    public function statusCounts(): array
    {
        return [
            'all' => $this->query()->count(),
            'ordered' => $this->query()->where('status', 'ordered')->count(),
            'processing' => $this->query()->where('status', 'processing')->count(),
            'completed' => $this->query()->where('status', 'completed')->count(),
            'cancelled' => $this->query()->where('status', 'cancelled')->count(),
        ];
    }

    public function paymentMethodCounts(string $status): array
    {
        $query = $this->query()
            ->when(in_array($status, ['ordered', 'processing', 'completed', 'cancelled'], true), function ($builder) use ($status) {
                $builder->where('status', $status);
            });

        return [
            'all' => (clone $query)->count(),
            'qris' => (clone $query)->where('payment_method', 'qris')->count(),
            'bill' => (clone $query)->where('payment_method', 'bill')->count(),
        ];
    }

    public function paginateFiltered(string $status, string $paymentMethod, int $perPage = 10)
    {
        return $this->baseQuery()
            ->with(['player'])
            ->when(in_array($status, ['ordered', 'processing', 'completed', 'cancelled'], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when(in_array($paymentMethod, ['qris', 'bill'], true), function ($query) use ($paymentMethod) {
                $query->where('payment_method', $paymentMethod);
            })
            ->orderByRaw("CASE WHEN status = 'completed' THEN 1 ELSE 0 END ASC")
            ->orderBy('created_at')
            ->paginate($perPage);
    }

    public function findWithRelations(int|string $id): ?MenuTransaction
    {
        return $this->baseQuery()->find($id);
    }

    public function baseQuery()
    {
        return $this->query()->with([
            'invoice',
            'player',
            'details.menu.imageMedia',
            'createdBy',
            'processedBy',
            'completedBy',
            'cancelledBy',
        ]);
    }
}
