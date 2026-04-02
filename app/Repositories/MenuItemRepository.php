<?php

namespace App\Repositories;

use App\Models\MenuItem;
use Yajra\DataTables\Facades\DataTables;

class MenuItemRepository extends BaseRepository
{
    public function __construct(MenuItem $item)
    {
        parent::__construct($item);
    }

    public function create(array $attributes)
    {
        if (isset($attributes['_token'])) {
            unset($attributes['_token']);
        }

        return $this->model->create($attributes);
    }

    public function updateByUid($uid, array $attributes)
    {
        if (isset($attributes['_token'])) {
            unset($attributes['_token']);
        }

        $record = $this->findUid($uid);

        if ($record) {
            $record->update($attributes);
            return $record;
        }

        return false;
    }

    public function delete($uid, $fieldName = 'image_id', $destroyImage = true)
    {
        $item = $this->findUid($uid);
        if ($item) {
            $item->deleted_by = auth()->id();
            $item->save();
            return $item->delete();
        }
        return false;
    }

    public function bulkDeleteByUid(array $uids, $fieldName = 'image_id', $destroyImage = true)
    {
        if (empty($uids)) {
            return 0;
        }

        return $this->model->whereIn('uuid', $uids)->update([
            'deleted_by' => auth()->id(),
            'deleted_at' => now(),
        ]);
    }

    public function getDatatable()
    {
        $query = $this->query()
            ->with(['tenant', 'category', 'imageMedia'])
            ->filter(request(['search', 'filters']));

        return DataTables::of($this->paginateDatatable($query))
            ->addIndexColumn()
            ->addColumn('tenant', fn ($row) => optional($row->tenant)->name)
            ->addColumn('category', fn ($row) => optional($row->category)->name)
            ->addColumn('price_display', fn ($row) => number_format($row->price, 2))
            ->addColumn('discount_display', fn ($row) => $row->discount_price ? number_format($row->discount_price, 2) : '')
            ->addColumn('action', function ($row) {
                return view('partials.datatable.action2', [
                    'row' => $row
                ])->render();
            })
            ->rawColumns(['action'])
            ->make(true);
    }
}
