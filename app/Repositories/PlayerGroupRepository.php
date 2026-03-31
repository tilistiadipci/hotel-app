<?php

namespace App\Repositories;

use App\Models\Booking;
use App\Models\PlayerGroup;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class PlayerGroupRepository extends BaseRepository
{
    public function __construct(PlayerGroup $playerGroup)
    {
        parent::__construct($playerGroup);
    }

    public function create(array $attributes)
    {
        $attributes['is_active'] = $attributes['is_active'] ?? true;

        return parent::create($attributes);
    }

    public function delete($uid, $fieldName = null, $destroyImage = false)
    {
        $record = $this->findUid($uid);
        if ($record) {
            $record->deleted_by = auth()->id();
            $record->save();
            return $record->delete();
        }
        return false;
    }

    public function bulkDeleteByUid(array $uids, $fieldName = null, $destroyImage = false)
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
            ->withCount(['players'])
            ->filter(request(['search', 'filters']));

        return DataTables::of($this->paginateDatatable($query))
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                return view('partials.datatable.action2', [
                    'row' => $row
                ])->render();
            })
            ->rawColumns(['action'])
            ->make(true);
    }
}
