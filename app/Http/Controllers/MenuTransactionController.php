<?php

namespace App\Http\Controllers;

use App\Models\MenuTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MenuTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $activeStatus = $request->input('status', 'ordered');
        $statusCounts = [
            'all' => MenuTransaction::query()->count(),
            'ordered' => MenuTransaction::query()->where('status', 'ordered')->count(),
            'processing' => MenuTransaction::query()->where('status', 'processing')->count(),
            'completed' => MenuTransaction::query()->where('status', 'completed')->count(),
            'cancelled' => MenuTransaction::query()->where('status', 'cancelled')->count(),
        ];

        $transactionsPaginator = MenuTransaction::query()
            ->with(['invoice', 'player', 'details.menu.imageMedia', 'createdBy'])
            ->when(in_array($activeStatus, ['ordered', 'processing', 'completed', 'cancelled'], true), function ($query) use ($activeStatus) {
                $query->where('status', $activeStatus);
            })
            ->orderByRaw("CASE WHEN status = 'completed' THEN 1 ELSE 0 END ASC")
            ->orderByDesc('created_at')
            ->paginate(10);

        $transactions = $transactionsPaginator->getCollection();

        $selectedId = $request->input('transaction_id');
        $selectedTransaction = $transactions->firstWhere('id', (int) $selectedId) ?? $transactions->first();

        if ($request->ajax()) {
            if ($request->input('partial') === 'list') {
                return response()->json([
                    'html' => view('pages.transactions.components.list', [
                        'transactions' => $transactions,
                        'selectedTransaction' => $selectedTransaction,
                    ])->render(),
                    'has_more' => $transactionsPaginator->hasMorePages(),
                    'next_page' => $transactionsPaginator->currentPage() + 1,
                    'active_status' => $activeStatus,
                ]);
            }

            return response()->view('pages.transactions.components.detail', [
                'selectedTransaction' => $selectedTransaction,
            ]);
        }

        return view('pages.transactions.index', [
            'page' => 'transactions',
            'transactions' => $transactions,
            'selectedTransaction' => $selectedTransaction,
            'hasMoreTransactions' => $transactionsPaginator->hasMorePages(),
            'nextTransactionPage' => $transactionsPaginator->currentPage() + 1,
            'activeStatus' => $activeStatus,
            'statusCounts' => $statusCounts,
        ]);
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['processing', 'completed'])],
        ]);

        $transaction = MenuTransaction::query()
            ->with(['invoice', 'player', 'details.menu.imageMedia', 'createdBy'])
            ->findOrFail($id);

        if ($validated['status'] === 'processing' && $transaction->status !== 'ordered') {
            return response()->json([
                'message' => 'Transaction cannot be processed from the current status.',
            ], 422);
        }

        if ($validated['status'] === 'completed' && !in_array($transaction->status, ['ordered', 'processing'], true)) {
            return response()->json([
                'message' => 'Transaction cannot be completed from the current status.',
            ], 422);
        }

        $transaction->status = $validated['status'];
        $transaction->updated_by = auth()->id();

        if ($validated['status'] === 'completed' && $transaction->payment_status === 'pending') {
            $transaction->payment_status = 'paid';
            $transaction->paid_at = now();
        }

        $transaction->save();
        $transaction->refresh()->load(['invoice', 'player', 'details', 'createdBy']);

        return response()->json([
            'message' => 'Transaction updated successfully.',
            'detail_html' => view('pages.transactions.components.detail', [
                'selectedTransaction' => $transaction,
            ])->render(),
        ]);
    }
}
