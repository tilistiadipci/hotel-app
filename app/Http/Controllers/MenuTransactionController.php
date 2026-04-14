<?php

namespace App\Http\Controllers;

use App\Models\MenuTenant;
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
        $tenantContext = $this->resolveTenantContext($request);
        $activeStatus = $request->input('status', 'ordered');
        $activePaymentMethod = $this->normalizePaymentMethod($activeStatus, $request->input('payment_method', 'all'));
        $statusCounts = $this->menuTransactionRepository->statusCounts(
            $tenantContext['allowedTenantIds'],
            $tenantContext['activeTenantId']
        );
        $paymentMethodCounts = $this->menuTransactionRepository->paymentMethodCounts(
            $activeStatus,
            $tenantContext['allowedTenantIds'],
            $tenantContext['activeTenantId']
        );
        $transactionsPaginator = $this->menuTransactionRepository->paginateFiltered(
            $activeStatus,
            $activePaymentMethod,
            10,
            $tenantContext['allowedTenantIds'],
            $tenantContext['activeTenantId']
        );

        $transactions = $transactionsPaginator->getCollection();

        $selectedId = $request->input('transaction_id');
        $selectedTransaction = $transactions->firstWhere('id', (int) $selectedId) ?? $transactions->first();

        if (!$selectedTransaction && $selectedId) {
            $selectedTransaction = $this->menuTransactionRepository->findWithRelations(
                (int) $selectedId,
                $tenantContext['allowedTenantIds'],
                $tenantContext['activeTenantId']
            );
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
                    'detail_count' => $this->buildDetailCountPayload($statusCounts),
                    'active_tenant_id' => $tenantContext['activeTenantId'],
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
            'isOperatorView' => $tenantContext['isOperatorView'],
            'operatorTenants' => $tenantContext['operatorTenants'],
            'activeTenantId' => $tenantContext['activeTenantId'],
        ]);
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['processing', 'completed', 'cancelled'])],
            'tenant_id' => ['nullable', 'integer'],
        ]);
        $tenantContext = $this->resolveTenantContext($request);

        $transaction = $this->menuTransactionRepository->findWithRelations(
            (int) $id,
            $tenantContext['allowedTenantIds'],
            $tenantContext['activeTenantId']
        );

        if (!$transaction) {
            return response()->json([
                'message' => trans('common.error.404'),
            ], 404);
        }

        if (
            $transaction->payment_method === 'qris' &&
            $transaction->payment_status === 'pending'
        ) {
            return response()->json([
                'message' => 'QRIS transaction cannot be processed or completed while payment is still pending.',
            ], 422);
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

        if ($validated['status'] === 'cancelled' && !in_array($transaction->status, ['ordered', 'processing'], true)) {
            return response()->json([
                'message' => 'Transaction cannot be cancelled from the current status.',
            ], 422);
        }

        if ($validated['status'] === 'cancelled') {
            $this->menuTransactionRepository->cancel($transaction);
        } else {
            $transaction->status = $validated['status'];
            $transaction->updated_by = auth()->id();
        }

        if ($validated['status'] === 'processing') {
            $transaction->processed_by = auth()->id();
        }

        if (
            $validated['status'] === 'completed' &&
            $transaction->payment_method === 'qris' &&
            $transaction->payment_status === 'pending'
        ) {
            $transaction->payment_status = 'paid';
            $transaction->paid_at = now();
        }

        if ($validated['status'] === 'completed') {
            if (!$transaction->processed_by) {
                $transaction->processed_by = auth()->id();
            }

            $transaction->completed_by = auth()->id();
        }

        if ($validated['status'] !== 'cancelled') {
            $transaction->save();
        }
        $transaction->refresh()->load([
            'invoice',
            'tenant',
            'player',
            'details.menu.imageMedia',
            'createdBy',
            'processedBy',
            'completedBy',
            'cancelledBy',
        ]);

        $statusCounts = $this->menuTransactionRepository->statusCounts(
            $tenantContext['allowedTenantIds'],
            $tenantContext['activeTenantId']
        );
        $paymentMethodCounts = $this->menuTransactionRepository->paymentMethodCounts(
            $transaction->status,
            $tenantContext['allowedTenantIds'],
            $tenantContext['activeTenantId']
        );

        return response()->json([
            'message' => 'Transaction updated successfully.',
            'detail_count' => $this->buildDetailCountPayload($statusCounts),
            'payment_method_counts' => $paymentMethodCounts,
            'detail_html' => view('pages.transactions.components.detail', [
                'selectedTransaction' => $transaction,
            ])->render(),
            'active_tenant_id' => $tenantContext['activeTenantId'],
        ]);
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'tenant_id' => ['nullable', 'integer'],
        ]);
        $tenantContext = $this->resolveTenantContext($request);
        $transaction = $this->menuTransactionRepository->findWithRelations(
            (int) $id,
            $tenantContext['allowedTenantIds'],
            $tenantContext['activeTenantId']
        );

        if (!$transaction) {
            return response()->json([
                'message' => trans('common.error.404'),
            ], 404);
        }

        if (!in_array($transaction->status, ['ordered', 'processing'], true)) {
            return response()->json([
                'message' => 'Transaction cannot be cancelled from the current status.',
            ], 422);
        }

        $this->menuTransactionRepository->cancel($transaction);

        $transaction->refresh()->load([
            'invoice',
            'tenant',
            'player',
            'details.menu.imageMedia',
            'createdBy',
            'processedBy',
            'completedBy',
            'cancelledBy',
        ]);

        $statusCounts = $this->menuTransactionRepository->statusCounts(
            $tenantContext['allowedTenantIds'],
            $tenantContext['activeTenantId']
        );
        $paymentMethodCounts = $this->menuTransactionRepository->paymentMethodCounts(
            $transaction->status,
            $tenantContext['allowedTenantIds'],
            $tenantContext['activeTenantId']
        );

        return response()->json([
            'message' => 'Transaction cancelled successfully.',
            'detail_count' => $this->buildDetailCountPayload($statusCounts),
            'payment_method_counts' => $paymentMethodCounts,
            'detail_html' => view('pages.transactions.components.detail', [
                'selectedTransaction' => $transaction,
            ])->render(),
            'active_tenant_id' => $tenantContext['activeTenantId'],
        ]);
    }

    private function normalizePaymentMethod(string $status, string $paymentMethod): string
    {
        return in_array($paymentMethod, ['qris', 'bill'], true) ? $paymentMethod : 'all';
    }

    private function resolveTenantContext(Request $request): array
    {
        $user = $request->user();
        $isOperatorView = $user?->hasRoleCategory('operator') ?? false;

        if (!$isOperatorView) {
            return [
                'isOperatorView' => false,
                'operatorTenants' => collect(),
                'allowedTenantIds' => null,
                'activeTenantId' => null,
            ];
        }

        $operatorTenants = $user->menuTenants()
            ->select('menu_tenants.id', 'menu_tenants.name', 'menu_tenants.sort_order')
            ->whereNull('menu_tenants.deleted_at')
            ->orderBy('menu_tenants.sort_order')
            ->orderBy('menu_tenants.name')
            ->get();

        if ($operatorTenants->isEmpty() && (int) ($user->menu_tenant_id ?? 0) > 0) {
            $fallbackTenant = MenuTenant::query()
                ->select('id', 'name', 'sort_order')
                ->where('id', (int) $user->menu_tenant_id)
                ->whereNull('deleted_at')
                ->first();

            if ($fallbackTenant) {
                $operatorTenants = collect([$fallbackTenant]);
            }
        }

        $allowedTenantIds = $operatorTenants->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $requestedTenantId = (int) $request->input('tenant_id');
        $activeTenantId = null;

        if (!empty($allowedTenantIds)) {
            $activeTenantId = in_array($requestedTenantId, $allowedTenantIds, true)
                ? $requestedTenantId
                : $allowedTenantIds[0];
        }

        return [
            'isOperatorView' => true,
            'operatorTenants' => $operatorTenants,
            'allowedTenantIds' => $allowedTenantIds,
            'activeTenantId' => $activeTenantId,
        ];
    }

    private function buildDetailCountPayload(array $statusCounts): array
    {
        return [
            'all' => $statusCounts['all'] ?? 0,
            'completed' => $statusCounts['completed'] ?? 0,
            'cancelled' => $statusCounts['cancelled'] ?? 0,
            'processing' => $statusCounts['processing'] ?? 0,
            'ordered' => $statusCounts['ordered'] ?? 0,
        ];
    }
}
