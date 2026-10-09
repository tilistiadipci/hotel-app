<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use JsonException;
use RuntimeException;

class WilayahIndonesiaImporter
{
    private const REQUIRED_TABLES = [
        'master_provinsi',
        'master_kabupaten_kota',
        'master_kecamatan',
        'master_kelurahan_desa',
    ];

    /**
     * @return array{provinsi: int, kabupaten_kota: int, kecamatan: int, kelurahan_desa: int}
     */
    public function import(?string $source = null): array
    {
        $source ??= base_path('kodepos.extended.json');

        foreach (self::REQUIRED_TABLES as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Tabel {$table} belum tersedia. Jalankan migration terlebih dahulu.");
            }
        }

        if (! is_file($source) || ! is_readable($source)) {
            throw new RuntimeException("File sumber wilayah tidak ditemukan atau tidak dapat dibaca: {$source}");
        }

        try {
            $regions = json_decode((string) file_get_contents($source), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Format JSON sumber wilayah tidak valid: '.$exception->getMessage(), 0, $exception);
        }

        if (! is_array($regions)) {
            throw new RuntimeException('Format sumber wilayah tidak valid. Data provinsi harus berupa object JSON.');
        }

        $counts = [
            'provinsi' => 0,
            'kabupaten_kota' => 0,
            'kecamatan' => 0,
            'kelurahan_desa' => 0,
        ];

        $provinces = [];
        $regencies = [];
        $districts = [];
        $villages = [];

        foreach ($regions as $provinceName => $province) {
            $provinceId = trim((string) ($province['ID'] ?? ''));
            if ($provinceId === '') {
                continue;
            }

            $provinces[] = ['id' => $provinceId, 'nama' => trim((string) $provinceName)];
            $counts['provinsi']++;

            foreach (($province['Kabupaten/Kota'] ?? []) as $regencyName => $regency) {
                $regencyId = trim((string) ($regency['ID'] ?? ''));
                if ($regencyId === '') {
                    continue;
                }

                $regencies[] = [
                    'id' => $regencyId,
                    'provinsi_id' => $provinceId,
                    'nama' => trim((string) $regencyName),
                    'tipe' => $this->normalizeRegencyType($regency['Type'] ?? null),
                ];
                $counts['kabupaten_kota']++;

                foreach (($regency['Kecamatan'] ?? []) as $districtName => $district) {
                    $districtId = trim((string) ($district['ID'] ?? ''));
                    if ($districtId === '') {
                        continue;
                    }

                    $districts[] = [
                        'id' => $districtId,
                        'kabupaten_kota_id' => $regencyId,
                        'nama' => trim((string) $districtName),
                    ];
                    $counts['kecamatan']++;

                    foreach (($district['Kelurahan/Desa'] ?? []) as $villageName => $village) {
                        $villageId = trim((string) ($village['ID'] ?? ''));
                        if ($villageId === '') {
                            continue;
                        }

                        $postalCode = trim((string) ($village['Kode Pos'] ?? ''));
                        $villages[] = [
                            'id' => $villageId,
                            'kecamatan_id' => $districtId,
                            'nama' => trim((string) $villageName),
                            'kode_pos' => $postalCode !== '' ? $postalCode : null,
                        ];
                        $counts['kelurahan_desa']++;
                    }
                }
            }
        }

        DB::transaction(function () use ($provinces, $regencies, $districts, $villages): void {
            $this->upsertInChunks('master_provinsi', $provinces, ['nama']);
            $this->upsertInChunks('master_kabupaten_kota', $regencies, ['provinsi_id', 'nama', 'tipe']);
            $this->upsertInChunks('master_kecamatan', $districts, ['kabupaten_kota_id', 'nama']);
            $this->upsertInChunks('master_kelurahan_desa', $villages, ['kecamatan_id', 'nama', 'kode_pos']);
        });

        return $counts;
    }

    private function normalizeRegencyType(mixed $type): ?string
    {
        $type = trim((string) $type);

        return in_array($type, ['Kabupaten', 'Kota'], true) ? $type : null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $updateColumns
     */
    private function upsertInChunks(string $table, array $rows, array $updateColumns): void
    {
        foreach (array_chunk($rows, 750) as $chunk) {
            DB::table($table)->upsert($chunk, ['id'], $updateColumns);
        }
    }
}
