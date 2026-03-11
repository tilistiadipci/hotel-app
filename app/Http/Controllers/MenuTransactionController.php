<?php

namespace App\Http\Controllers;

use App\Repositories\MenuTransactionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MenuTransactionController extends Controller
{
    public function __construct(
        private readonly MenuTransactionRepository $menuTransactionRepository
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $activeStatus = $request->input('status', 'ordered');
        $activePaymentMethod = $this->normalizePaymentMethod($activeStatus, $request->input('payment_method', 'all'));
        $statusCounts = $this->menuTransactionRepository->statusCounts();
        $paymentMethodCounts = $this->menuTransactionRepository->paymentMethodCounts($activeStatus);
        $transactionsPaginator = $this->menuTransactionRepository->paginateFiltered($activeStatus, $activePaymentMethod, 10);

        $transactions = $transactionsPaginator->getCollection();

        $selectedId = $request->input('transaction_id');
        $selectedTransaction = $transactions->firstWhere('id', (int) $selectedId) ?? $transactions->first();

        if (!$selectedTransaction && $selectedId) {
            $selectedTransaction = $this->menuTransactionRepository->findWithRelations((int) $selectedId);
        }

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
                    'active_payment_method' => $activePaymentMethod,
                    'selected_transaction_id' => $selectedTransaction?->id,
                    'payment_method_counts' => $paymentMethodCounts,
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
            'activePaymentMethod' => $activePaymentMethod,
            'statusCounts' => $statusCounts,
            'paymentMethodCounts' => $paymentMethodCounts,
        ]);
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['processing', 'completed'])],
        ]);

        $transaction = $this->menuTransactionRepository->findWithRelations((int) $id);

        if (!$transaction) {
            return response()->json([
                'message' => trans('common.error.404'),
            ], 404);
        }

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

        if ($validated['status'] === 'processing') {
            $transaction->processed_by = auth()->id();
        }

        if ($validated['status'] === 'completed' && $transaction->payment_status === 'pending') {
            $transaction->payment_status = 'paid';
            $transaction->paid_at = now();
        }

        if ($validated['status'] === 'completed') {
            if (!$transaction->processed_by) {
                $transaction->processed_by = auth()->id();
            }

            $transaction->completed_by = auth()->id();
        }

        $transaction->save();
        $transaction->refresh()->load([
            'invoice',
            'player',
            'details.menu.imageMedia',
            'createdBy',
            'processedBy',
            'completedBy',
            'cancelledBy',
        ]);

        $statusCounts = $this->menuTransactionRepository->statusCounts();
        $paymentMethodCounts = $this->menuTransactionRepository->paymentMethodCounts($transaction->status);

        return response()->json([
            'message' => 'Transaction updated successfully.',
            'detail_count' => [
                'all' => $statusCounts['all'],
                'completed' => $statusCounts['completed'],
                'cancelled' => $statusCounts['cancelled'],
                'processing' => $statusCounts['processing'],
                'ordered' => $statusCounts['ordered'],
            ],
            'payment_method_counts' => $paymentMethodCounts,
            'detail_html' => view('pages.transactions.components.detail', [
                'selectedTransaction' => $transaction,
            ])->render(),
        ]);
    }

    private function normalizePaymentMethod(string $status, string $paymentMethod): string
    {
        return in_array($paymentMethod, ['qris', 'bill'], true) ? $paymentMethod : 'all';
    }
}
