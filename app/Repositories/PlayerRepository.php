<?php

namespace App\Repositories;

use App\Models\Booking;
use App\Models\Player;
use Yajra\DataTables\Facades\DataTables;

class PlayerRepository extends BaseRepository
{
    public function __construct(Player $player)
    {
        parent::__construct($player);
    }

    public function create(array $attributes)
    {
        $attributes['is_active'] = $attributes['is_active'] ?? true;
        $attributes['theme_id'] = $attributes['theme_id'] ?? 1;

        return parent::create($attributes);
    }

    public function updateByUid($uid, array $attributes)
    {
        $attributes['is_active'] = $attributes['is_active'] ?? true;
        $attributes['theme_id'] = $attributes['theme_id'] ?? 1;

        return parent::updateByUid($uid, $attributes);
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
            ->with(['theme'])
            ->filter(request(['search', 'filters']));

        return DataTables::of($this->paginateDatatable($query))
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                return view('partials.datatable.action2', [
                    'row' => $row
                ])->render();
            })
            ->addColumn('theme_name', function ($row) {
                return $row->theme ? $row->theme->name : '-';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function findUidForUpdate(string $uid): ?Player
    {
        return $this->query()
            ->where('uuid', $uid)
            ->lockForUpdate()
            ->first();
    }

    public function hasActiveBooking(int $playerId): bool
    {
        return Booking::query()
            ->active()
            ->where('player_id', $playerId)
            ->exists();
    }
}
