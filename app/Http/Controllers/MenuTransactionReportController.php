<?php

namespace App\Http\Controllers;

use App\Models\MenuTenant;
use App\Models\Player;
use App\Repositories\MenuTransactionReportRepository;
use Illuminate\Http\Request;

class MenuTransactionReportController extends Controller
{
    private string $page = 'report-menu-transactions';
    private string $icon = 'fa fa-receipt';

    public function __construct(
        private readonly MenuTransactionReportRepository $menuTransactionReportRepository
    ) {
    }

    public function index(Request $request)
    {
        $filters = $request->only([
            'daterange',
            'player_ids',
            'menu_tenant_id',
            'payment_status',
            'payment_method',
        ]);

        $selectedPlayerIds = array_filter((array) $request->input('player_ids', []));
        $players = Player::query()
            ->select(['id', 'name', 'alias'])
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();
        $tenants = MenuTenant::query()
            ->select(['id', 'name'])
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('pages.reports.menu-transactions', [
            'page' => $this->page,
            'icon' => $this->icon,
            'filters' => $filters,
            'players' => $players,
            'tenants' => $tenants,
            'selectedPlayerIds' => $selectedPlayerIds,
        ]);
    }

    public function data(Request $request)
    {
        $filters = $request->only([
            'daterange',
            'player_ids',
            'menu_tenant_id',
            'payment_status',
            'payment_method',
        ]);

        return $this->menuTransactionReportRepository->getDatatable($filters);
    }

    public function export(Request $request)
    {
        $filters = $request->only([
            'daterange',
            'player_ids',
            'menu_tenant_id',
            'payment_status',
            'payment_method',
        ]);

        $offset = (int) $request->input('offset', 0);
        $limit = (int) $request->input('limit', 500);
        $limit = max(1, min($limit, 2000));

        return response()->json(
            $this->menuTransactionReportRepository->getChunk($filters, $offset, $limit)
        );
    }
}
