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
        $result = $this->query()
            ->selectRaw("COUNT(*) as total")
            ->selectRaw("SUM(status = 'ordered') as ordered")
            ->selectRaw("SUM(status = 'processing') as processing")
            ->selectRaw("SUM(status = 'completed') as completed")
            ->selectRaw("SUM(status = 'cancelled') as cancelled")
            ->first();

        return [
            'all' => (int) ($result->total ?? 0),
            'ordered' => (int) ($result->ordered ?? 0),
            'processing' => (int) ($result->processing ?? 0),
            'completed' => (int) ($result->completed ?? 0),
            'cancelled' => (int) ($result->cancelled ?? 0),
        ];
    }

    public function paymentMethodCounts(string $status): array
    {
        $query = $this->query()
            ->when(in_array($status, ['ordered', 'processing', 'completed', 'cancelled'], true), function ($builder) use ($status) {
                $builder->where('status', $status);
            })
            ->selectRaw("COUNT(*) as total")
            ->selectRaw("SUM(payment_method = 'qris') as qris")
            ->selectRaw("SUM(payment_method = 'bill') as bill")
            ->first();

        return [
            'all' => (int) ($query->total ?? 0),
            'qris' => (int) ($query->qris ?? 0),
            'bill' => (int) ($query->bill ?? 0),
        ];
    }

    public function paginateFiltered(string $status, string $paymentMethod, int $perPage = 10)
    {
        return $this->baseListQuery()
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

    public function cancel(MenuTransaction $transaction): MenuTransaction
    {
        $transaction->status = 'cancelled';
        $transaction->payment_status = 'cancelled';
        $transaction->cancelled_by = auth()->id();
        $transaction->updated_by = auth()->id();
        $transaction->save();

        return $transaction;
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

    public function baseListQuery()
    {
        return $this->query()
            ->with(['invoice', 'player'])
            ->withCount('details');
    }
}
