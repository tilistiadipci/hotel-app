<?php

namespace App\Repositories;

use App\Models\TvChannel;
use Yajra\DataTables\Facades\DataTables;

class TVChannelRepository extends BaseRepository
{
    public function __construct(TvChannel $channel)
    {
        parent::__construct($channel);
    }

    public function findUid($uid)
    {
        return $this->model->where('uuid', $uid)->first();
    }

    public function updateByUid($uid, array $attributes)
    {
        $record = $this->findUid($uid);

        if (isset($attributes['_token'])) {
            unset($attributes['_token']);
        }

        $attributes['updated_by'] = auth()->user()->id ?? null;

        if ($record) {
            $record->update($attributes);
            return $record;
        }

        return false;
    }

    public function delete($uid, $fieldName = 'logo', $destroyImage = true)
    {
        $record = $this->findUid($uid);
        if ($record) {
            $record->deleted_by = auth()->user()->id ?? null;
            $record->deleted_at = now();
            return $record->save();
        }
        return false;
    }

    public function bulkDeleteByUid(array $uids, $fieldName = 'logo', $destroyImage = true)
    {
        if (empty($uids)) {
            return 0;
        }

        return $this->model->whereIn('uuid', $uids)->update([
            'deleted_by' => auth()->user()->id ?? null,
            'deleted_at' => now(),
        ]);
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
