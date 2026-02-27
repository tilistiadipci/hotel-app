<?php

namespace App\Repositories;

use App\Models\Movie;
use Yajra\DataTables\Facades\DataTables;

class MovieRepository extends BaseRepository
{
    public function __construct(Movie $movie)
    {
        parent::__construct($movie);
    }

    public function create(array $attributes)
    {
        if (isset($attributes['_token'])) {
            unset($attributes['_token']);
        }

        $movie = $this->model->create($attributes);

        if (!empty($attributes['category_ids'])) {
            $movie->categories()->sync($attributes['category_ids']);
        }

        return $movie;
    }

    public function updateByUid($uid, array $attributes)
    {
        if (isset($attributes['_token'])) {
            unset($attributes['_token']);
        }

        $movie = $this->findUid($uid);

        if ($movie) {
            $movie->update($attributes);
            if (isset($attributes['category_ids'])) {
                $movie->categories()->sync($attributes['category_ids']);
            }
            return $movie;
        }

        return false;
    }

    public function delete($uid, $fieldName = 'thumbnail', $destroyImage = true)
    {
        $movie = $this->findUid($uid);
        if ($movie) {
            $movie->deleted_by = auth()->user()->id ?? null;
            $movie->deleted_at = now();
            return $movie->save();
        }
        return false;
    }

    public function bulkDeleteByUid(array $uids, $fieldName = 'thumbnail', $destroyImage = true)
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
        $query = $this->query()->with(['categories', 'imageMedia', 'videoMedia'])->filter(request(['search', 'filters']));

        return DataTables::of($this->paginateDatatable($query))
            ->addIndexColumn()
            ->addColumn('categories', function ($row) {
                return $row->categories->pluck('name')->implode(', ');
            })
            ->addColumn('action', function ($row) {
                // dd($row->videoMedia->storage_path);
                return view('partials.datatable.action2', [
                    'row' => $row,
                    'movie' => true,
                ])->render();
            })
            ->addColumn('movie_name', function ($row) {
                return $row->videoMedia->name ?? '-';
            })
            ->rawColumns(['action'])
            ->make(true);
    }
}
