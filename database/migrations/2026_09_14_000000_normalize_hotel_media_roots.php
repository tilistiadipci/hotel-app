<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $baseRoot = rtrim((string) config('filesystems.media_base_root'), "/\\");
        $usedRoots = [];
        $plans = DB::table('hotel_configurations as hc')
            ->join('hotels as h', 'h.id', '=', 'hc.hotel_id')
            ->orderBy('h.created_at')
            ->get(['hc.id', 'hc.media_root', 'h.name', 'h.code'])
            ->map(function ($configuration) use (&$usedRoots, $baseRoot) {
                $base = Str::slug($configuration->name) ?: Str::slug($configuration->code) ?: 'hotel';
                $suffix = 1;
                do {
                    $mediaRoot = '/'.$base.($suffix > 1 ? '-'.$suffix : '');
                    $suffix++;
                } while (in_array($mediaRoot, $usedRoots, true));
                $usedRoots[] = $mediaRoot;

                return (object) [
                    'id' => $configuration->id,
                    'media_root' => $mediaRoot,
                    'source' => $this->legacyAbsoluteRoot((string) $configuration->media_root, $baseRoot),
                    'target' => $baseRoot.DIRECTORY_SEPARATOR.ltrim(str_replace('/', DIRECTORY_SEPARATOR, $mediaRoot), "/\\"),
                ];
            });

        $targetKeys = $plans->map(fn ($plan) => $this->pathKey($plan->target))->all();

        foreach ($plans as $plan) {
            if ($this->pathKey($plan->source) !== $this->pathKey($plan->target) && File::isDirectory($plan->source)) {
                File::ensureDirectoryExists($plan->target);

                foreach (File::directories($plan->source) as $directory) {
                    if (in_array($this->pathKey($directory), $targetKeys, true)) {
                        continue;
                    }

                    $destination = $plan->target.DIRECTORY_SEPARATOR.basename($directory);
                    if (! File::exists($destination)) {
                        File::moveDirectory($directory, $destination);
                    }
                }

                foreach (File::files($plan->source) as $file) {
                    $destination = $plan->target.DIRECTORY_SEPARATOR.$file->getFilename();
                    if (! File::exists($destination)) {
                        File::move($file->getPathname(), $destination);
                    }
                }
            }

            DB::table('hotel_configurations')->where('id', $plan->id)->update([
                'media_root' => $plan->media_root,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Media folders are intentionally not merged back into the shared root.
    }

    private function legacyAbsoluteRoot(string $mediaRoot, string $baseRoot): string
    {
        $normalized = str_replace('\\', '/', trim($mediaRoot));
        $normalizedBase = rtrim(str_replace('\\', '/', $baseRoot), '/');

        if (str_starts_with(strtolower($normalized), strtolower($normalizedBase)) || preg_match('/^[a-zA-Z]:\//', $normalized)) {
            return rtrim($mediaRoot, "/\\");
        }

        return $baseRoot.DIRECTORY_SEPARATOR.ltrim(str_replace('/', DIRECTORY_SEPARATOR, $mediaRoot), "/\\");
    }

    private function pathKey(string $path): string
    {
        return strtolower(rtrim(str_replace('\\', '/', $path), '/'));
    }
};
