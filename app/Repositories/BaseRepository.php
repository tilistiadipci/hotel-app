<?php

namespace App\Repositories;

use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BaseRepository
{
    protected $model;
    protected $query;

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->query = $model->newQuery();
    }

    public function paginateDatatable($query, $withTrashed = false)
    {
        $request = request();
        $length = $request->input('length');
        $start = $request->input('start');

        if ($withTrashed) {
            return $query->withTrashed()->get();
        }

        // Tambahkan pembatasan (limit) dan offset (start)
        return $query->get();
    }

    public function getQty($id)
    {
        return $this->query->where('id', $id)->first()->qty;
    }


    public function query()
    {
        return $this->model->query();
    }

    public function count()
    {
        return $this->model->count();
    }

    public function getAllWithTrashed()
    {
        return $this->model->withTrashed()->get();
    }

    public function getAllWithPaginate($limit = 20)
    {
        return $this->model->paginate($limit);
    }

    public function all()
    {
        return $this->model->all();
    }

    public function get()
    {
        return $this->model->get();
    }

    public function create(array $attributes)
    {
        if (isset($attributes['_token'])) {
            unset($attributes['_token']);
        }

        if (auth()->check()) {
            $attributes['created_by'] = auth()->user()->id;
        }

        return $this->model->create($attributes);
    }

    public function update($id, array $attributes)
    {
        $record = $this->find($id);

        if (isset($attributes['_token'])) {
            unset($attributes['_token']);
        }

        $attributes['updated_by'] = auth()->user()->id;

        if ($record) {
            $record->update($attributes);
            return $record;
        }
        return false;
    }

    public function updateByUid($uid, array $attributes)
    {
        $record = $this->findUid($uid);

        if (isset($attributes['_token'])) {
            unset($attributes['_token']);
        }

        $attributes['updated_by'] = auth()->user()->id;

        if ($record) {
            $record->update($attributes);
            return $record;
        }
        return false;
    }

    public function find($id)
    {
        return $this->model->find($id);
    }

    public function findUid($uid)
    {
        return $this->model->where('uuid', $uid)->first();
    }

    public function findOrFail($id)
    {
        return $this->model->findOrFail($id);
    }

    public function delete($uid, $fieldName = 'image', $destroyImage = true)
    {
        $record = $this->findUid($uid);
        if ($record) {
            $record->deleted_by = auth()->user()->id;
            $record->deleted_at = Carbon::now();
            $result = $record->save();

            if ($result && $record->$fieldName != '/images/default.png' && $destroyImage) {
                $this->destroyImage($record, $fieldName);

            }
            return $result;
        }
        return false;
    }

    public function bulkDelete(array $ids, $fieldName = 'image', $destroyImage = true)
    {
        if ($destroyImage) {
            $records = $this->model->whereIn('id', $ids)
                    ->where($fieldName, '!=', '/images/default.png')
                    ->get(['id', $fieldName, 'deleted_by'])
                    ->toArray();
        }

        // delete user
        $result = $this->model->whereIn('id', $ids)->update([
            'deleted_by' => auth()->user()->id,
            'deleted_at' => Carbon::now()
        ]);

        if ($result > 0 && $destroyImage) {
            foreach ($records as $record) {
                $this->destroyImage($record, $fieldName);
            }
        }

        return $result;
    }

    public function bulkDeleteByUid(array $uids, $fieldName = 'image', $destroyImage = true)
    {
        if (empty($uids)) {
            return 0;
        }

        $query = $this->model->whereIn('uuid', $uids);

        if ($destroyImage) {
            $records = $query->where($fieldName, '!=', '/images/default.png')
                ->get(['uuid', $fieldName, 'deleted_by'])
                ->toArray();
        }

        $result = $query->update([
            'deleted_by' => auth()->user()->id,
            'deleted_at' => Carbon::now(),
        ]);

        if ($result > 0 && $destroyImage && !empty($records)) {
            foreach ($records as $record) {
                $this->destroyImage($record, $fieldName);
            }
        }

        return $result;
    }

    public function whereLike($wheres = [])
    {
        $query = $this->model;

        foreach ($wheres as $key => $value) {
            $query->where($key, 'like', '%' . $value . '%');
        }

        return $query;
    }

    public function where($wheres = [])
    {
        return $this->model->where($wheres);
    }

    public function whereWith($relations = [], $wheres = [])
    {
        $query = $this->model->with($relations);

        if (!empty($wheres)) {
            $query->where($wheres);
        }

        return $query;
    }

    public function getSlug($name): string
    {
        $slug = Str::slug($name, '-');
        $count = $this->whereLike(['slug' => $slug])->count();

        if ($count> 0) {
            $slug = $slug . '-' . $count++;
        }

        return $slug;
    }

    public function destroyImage($record, $fieldName = 'image'): void
    {
        if (isset($record[$fieldName])) {
            $relativePath = str_replace('storage/', '', $record[$fieldName]);
            $absolutePath = storage_path('app/public/' . $relativePath);

            // dd($absolutePath); // Debug absolute path

            if (file_exists($absolutePath)) {
                // dd('File exists at: ' . storage_path('app/public/' . $absolutePath));
                unlink($absolutePath);
            }
        }
    }

    public function modifyDate($request, $name = 'purchase_date')
    {
        if (isset($request[$name]) && $request[$name]) {
            $data[$name] = Carbon::createFromFormat('d/m/Y', $request[$name])
                                    ->toDateString();

            $request = collect($request)->merge($data);

            return $request->toArray();
        }

        return $request;
    }
}
