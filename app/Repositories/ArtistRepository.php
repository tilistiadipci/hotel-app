<?php

namespace App\Repositories;

use App\Models\Artist;
use Yajra\DataTables\Facades\DataTables;

class ArtistRepository extends BaseRepository
{
    public function __construct(Artist $artist)
    {
        parent::__construct($artist);
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

    public function delete($uid, $fieldName = 'image', $destroyImage = true)
    {
        return $this->model->where('uuid', $uid)->delete();
    }

    public function bulkDeleteByUid(array $uids, $fieldName = 'image', $destroyImage = true)
    {
        if (empty($uids)) {
            return 0;
        }

        return $this->model->whereIn('uuid', $uids)->delete();
    }

    public function getDatatable()
    {
        $query = $this->query()->filter(request(['search', 'filters']));

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
