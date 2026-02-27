<?php

namespace App\Repositories;

use App\Models\Place;
use Yajra\DataTables\Facades\DataTables;

class PlaceRepository extends BaseRepository
{
    public function __construct(Place $place)
    {
        parent::__construct($place);
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
        $place = $this->findUid($uid);
        if ($place) {
            $place->deleted_by = auth()->id();
            $place->save();
            return $place->delete();
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
            ->with(['category', 'imageMedia'])
            ->filter(request(['search', 'filters']));

        return DataTables::of($this->paginateDatatable($query))
            ->addIndexColumn()
            ->addColumn('category', fn($row) => optional($row->category)->name)
            ->addColumn('action', function ($row) {
                return view('partials.datatable.action2', [
                    'row' => $row
                ])->render();
            })
            ->rawColumns(['action'])
            ->make(true);
    }
}
