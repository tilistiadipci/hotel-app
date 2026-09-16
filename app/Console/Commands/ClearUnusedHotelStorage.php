<?php

namespace App\Console\Commands;

use App\Models\Hotel;
use App\Services\UnusedHotelMediaCleaner;
use App\Tenancy\HotelMediaPath;
use Illuminate\Console\Command;
use InvalidArgumentException;

class ClearUnusedHotelStorage extends Command
{
    protected $signature = 'app:clear-storage-unused
        {--folderhotelnya= : Nama folder hotel sesuai hotel_configurations.media_root}
        {--dry-run : Tampilkan media yang akan dihapus tanpa mengubah file dan database}';

    protected $description = 'Hapus media dan file yang tidak lagi digunakan dari folder storage sebuah hotel';

    public function handle(UnusedHotelMediaCleaner $cleaner, HotelMediaPath $mediaPath): int
    {
        $folder = trim((string) $this->option('folderhotelnya'));

        if ($folder === '') {
            $this->error('Option --folderhotelnya wajib diisi. Contoh: --folderhotelnya=hotel-1');

            return self::INVALID;
        }

        try {
            $normalizedFolder = $mediaPath->normalize($folder);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::INVALID;
        }

        $hotel = Hotel::query()
            ->whereHas('configuration', fn ($query) => $query->whereIn('media_root', [
                $normalizedFolder,
                '/'.$normalizedFolder,
            ]))
            ->with('configuration')
            ->first();

        if (! $hotel) {
            $this->error("Folder hotel '{$normalizedFolder}' tidak terdaftar di hotel_configurations.");

            return self::FAILURE;
        }

        try {
            $result = $cleaner->clean($hotel, (bool) $this->option('dry-run'));
        } catch (\Throwable $exception) {
            report($exception);
            $this->error('Pembersihan gagal: '.$exception->getMessage());

            return self::FAILURE;
        }

        $mode = $result['dry_run'] ? 'DRY RUN' : 'SELESAI';
        $this->newLine();
        $this->info("[{$mode}] Storage hotel {$hotel->name}");
        $this->line('Folder: '.$result['root']);
        $this->table(['Pemeriksaan', 'Jumlah'], [
            ['Record media dipindai', $result['scanned_media']],
            ['Record media masih digunakan', $result['used_media']],
            ['Record media tidak digunakan', $result['unused_media']],
            ['File yang '.($result['dry_run'] ? 'akan dihapus' : 'dihapus'), $result['deleted_file_count']],
            ['Ruang yang '.($result['dry_run'] ? 'akan dibebaskan' : 'dibebaskan'), $this->humanBytes($result['freed_bytes'])],
        ]);

        if ($result['deleted_files'] !== []) {
            $this->newLine();
            $this->line($result['dry_run'] ? 'File yang akan dihapus:' : 'File yang dihapus:');
            foreach ($result['deleted_files'] as $path) {
                $this->line('  - '.$path);
            }
        }

        if ($result['dry_run']) {
            $this->comment('Tidak ada file atau data yang diubah. Jalankan kembali tanpa --dry-run untuk menghapus.');
        }

        return self::SUCCESS;
    }

    private function humanBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $value = $bytes / 1024;

        foreach ($units as $index => $unit) {
            if ($value < 1024 || $index === count($units) - 1) {
                return number_format($value, 2, ',', '.').' '.$unit;
            }

            $value /= 1024;
        }

        return $bytes.' B';
    }
}
