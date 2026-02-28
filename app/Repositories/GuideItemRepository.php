<?php

namespace App\Repositories;

use App\Models\GuideItem;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;

class GuideItemRepository extends BaseRepository
{
    public function __construct(GuideItem $item)
    {
        parent::__construct($item);
    }

    public function create(array $attributes)
    {
        if (isset($attributes['_token'])) {
            unset($attributes['_token']);
        }

        $attributes['uuid'] = Str::uuid();

        return $this->model->create($attributes);
    }

    public function update($id, array $attributes)
    {
        if (isset($attributes['_token'])) {
            unset($attributes['_token']);
        }

        $record = $this->find($id);

        if ($record) {
            $record->update($attributes);
            return $record;
        }

        return false;
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

    public function deleteById($id, $fieldName = 'image_id', $destroyImage = false)
    {
        $record = $this->find($id);
        if ($record) {
            $record->deleted_by = auth()->id();
            $record->save();
            return $record->delete(); // Soft delete
        }
        return false;
    }

    public function deleteByUid($uid, $fieldName = 'image_id', $destroyImage = false)
    {
        return $this->delete($uid, 'image_id', false);
    }

    public function bulkDeleteById(array $ids, $fieldName = 'image_id', $destroyImage = false)
    {
        return parent::bulkDelete($ids, 'image_id', false);
    }

    public function bulkDeleteByUid(array $uids, $fieldName = 'image_id', $destroyImage = false)
    {
        return parent::bulkDeleteByUid($uids, 'image_id', false);
    }

    public function findBySlug(string $slug)
    {
        return $this->model->where('slug', $slug)->first();
    }

    public function getDatatable()
    {
        $query = $this->query()
            ->with(['category', 'imageMedia'])
            ->filter(request(['search', 'filters']));

        return DataTables::of($this->paginateDatatable($query))
            ->addIndexColumn()
            ->addColumn('category', fn ($row) => optional($row->category)->name)
            ->addColumn('action', function ($row) {
                return view('partials.datatable.action2', [
                    'row' => $row
                ])->render();
            })
            ->rawColumns(['action'])
            ->make(true);
    }
}
