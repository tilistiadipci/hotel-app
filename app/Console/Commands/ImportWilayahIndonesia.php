<?php

namespace App\Console\Commands;

use App\Services\WilayahIndonesiaImporter;
use Illuminate\Console\Command;
use Throwable;

class ImportWilayahIndonesia extends Command
{
    protected $signature = 'wilayah:import
        {--source= : Lokasi file JSON wilayah (default: kodepos.extended.json)}';

    protected $description = 'Impor atau perbarui master wilayah Indonesia secara aman dan idempotent';

    public function handle(WilayahIndonesiaImporter $importer): int
    {
        $source = $this->option('source');

        try {
            $counts = $importer->import(is_string($source) && $source !== '' ? $source : null);
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Master wilayah Indonesia berhasil diimpor.');
        $this->table(['Data', 'Jumlah'], [
            ['Provinsi', $counts['provinsi']],
            ['Kabupaten/Kota', $counts['kabupaten_kota']],
            ['Kecamatan', $counts['kecamatan']],
            ['Kelurahan/Desa', $counts['kelurahan_desa']],
        ]);

        return self::SUCCESS;
    }
}
