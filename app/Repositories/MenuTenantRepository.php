<?php

namespace App\Repositories;

use App\Models\MenuTenant;
use Yajra\DataTables\Facades\DataTables;

class MenuTenantRepository extends BaseRepository
{
    public function __construct(MenuTenant $tenant)
    {
        parent::__construct($tenant);
    }

    public function findBySlug(string $slug)
    {
        return $this->model->where('slug', $slug)->whereNull('deleted_at')->first();
    }

    public function getDatatable()
    {
        $query = $this->query()->with('imageMedia')->filter(request(['search', 'filters']));

        return DataTables::of($this->paginateDatatable($query))
            ->addIndexColumn()
            ->addColumn('service_charge_display', fn ($row) => number_format((float) $row->service_charge, 2))
            ->addColumn('action', function ($row) {
                return view('partials.datatable.action2', [
                    'row' => $row,
                ])->render();
            })
            ->rawColumns(['action'])
            ->make(true);
    }
}
