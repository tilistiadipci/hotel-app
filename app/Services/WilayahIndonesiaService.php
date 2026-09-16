<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WilayahIndonesiaService
{
    public function find(?string $adm4): ?object
    {
        if (blank($adm4)) {
            return null;
        }

        $row = $this->query()->where('kelurahan.id', $adm4)->first();

        return $row ? $this->format($row) : null;
    }

    public function search(string $term, int $limit = 20): Collection
    {
        $term = trim($term);

        if (mb_strlen($term) < 2) {
            return collect();
        }

        $like = '%'.addcslashes($term, '%_\\').'%';

        return $this->query()
            ->where(function (Builder $query) use ($like): void {
                $query->where('kelurahan.id', 'like', $like)
                    ->orWhere('kelurahan.kode_pos', 'like', $like)
                    ->orWhere('kelurahan.nama', 'like', $like)
                    ->orWhere('kecamatan.nama', 'like', $like)
                    ->orWhere('kabupaten.nama', 'like', $like)
                    ->orWhere('provinsi.nama', 'like', $like);
            })
            ->orderByRaw('CASE WHEN kelurahan.kode_pos = ? OR kelurahan.id = ? THEN 0 ELSE 1 END', [$term, $term])
            ->orderBy('provinsi.nama')
            ->orderBy('kabupaten.nama')
            ->orderBy('kecamatan.nama')
            ->orderBy('kelurahan.nama')
            ->limit(min(max($limit, 1), 50))
            ->get()
            ->map(fn (object $row) => $this->format($row));
    }

    private function query(): Builder
    {
        return DB::table('master_kelurahan_desa as kelurahan')
            ->join('master_kecamatan as kecamatan', 'kecamatan.id', '=', 'kelurahan.kecamatan_id')
            ->join('master_kabupaten_kota as kabupaten', 'kabupaten.id', '=', 'kecamatan.kabupaten_kota_id')
            ->join('master_provinsi as provinsi', 'provinsi.id', '=', 'kabupaten.provinsi_id')
            ->select([
                'kelurahan.id as adm4',
                'kelurahan.nama as kelurahan_desa',
                'kelurahan.kode_pos',
                'kecamatan.nama as kecamatan',
                'kabupaten.nama as kabupaten_kota',
                'provinsi.nama as provinsi',
            ]);
    }

    private function format(object $row): object
    {
        $parts = array_filter([
            $row->kelurahan_desa,
            'Kec. '.$row->kecamatan,
            $row->kabupaten_kota,
            $row->provinsi,
        ]);

        return (object) [
            'id' => $row->adm4,
            'adm4' => $row->adm4,
            'text' => implode(' — ', $parts).($row->kode_pos ? ' — '.$row->kode_pos : ''),
            'kelurahan_desa' => $row->kelurahan_desa,
            'kecamatan' => $row->kecamatan,
            'kabupaten_kota' => $row->kabupaten_kota,
            'provinsi' => $row->provinsi,
            'kode_pos' => $row->kode_pos,
        ];
    }
}
