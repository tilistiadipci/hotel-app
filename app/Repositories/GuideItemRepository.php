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

    public function deleteById($id, $fieldName = 'image', $destroyImage = true)
    {
        $record = $this->find($id);
        if ($record) {
            $record->deleted_by = auth()->id();
            $record->save();
            $result = $record->delete(); // Soft delete

            if ($result && $destroyImage && $fieldName && $record->$fieldName && $record->$fieldName !== '/images/default.png') {
                $this->destroyImage($record, $fieldName);
            }

            return $result;
        }
        return false;
    }

    public function deleteByUid($uid, $fieldName = 'image', $destroyImage = true)
    {
        return $this->delete($uid, $fieldName, $destroyImage);
    }

    public function bulkDeleteById(array $ids, $fieldName = 'image', $destroyImage = true)
    {
        return parent::bulkDelete($ids, $fieldName, $destroyImage);
    }

    public function bulkDeleteByUid(array $uids, $fieldName = 'image', $destroyImage = true)
    {
        return parent::bulkDeleteByUid($uids, $fieldName, $destroyImage);
    }

    public function findBySlug(string $slug)
    {
        return $this->model->where('slug', $slug)->first();
    }

    public function getDatatable()
    {
        $query = $this->query()
            ->with('category')
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
