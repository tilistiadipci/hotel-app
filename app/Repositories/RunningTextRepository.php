<?php

namespace App\Repositories;

use App\Models\RunningText;
use Yajra\DataTables\Facades\DataTables;

class RunningTextRepository extends BaseRepository
{
    public function __construct(RunningText $runningText)
    {
        parent::__construct($runningText);
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
}
