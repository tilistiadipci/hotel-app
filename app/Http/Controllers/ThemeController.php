<?php

namespace App\Http\Controllers;

use App\Models\Theme;
use App\Models\ThemeDetail;
use App\Repositories\MediaRepository;
use App\Repositories\ThemeRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ThemeController extends Controller
{
    private string $page = 'themes';
    private string $icon = 'pe-7s-paint-bucket';

    public function __construct(
        private readonly MediaRepository $mediaRepository,
        private readonly ThemeRepository $themeRepository
    ) {
    }

    public function index()
    {
        return view('pages.themes.index', [
            'page' => $this->page,
            'icon' => $this->icon,
            'themes' => $this->themeRepository->getList(),
        ]);
    }

    public function edit(string $uuid)
    {
        $theme = $this->themeRepository->findUidWithRelations($uuid);

        if (!$theme) {
            return redirect()->route('error.404');
        }

        return view('pages.themes.edit', [
            'page' => $this->page,
            'icon' => $this->icon,
            'theme' => $theme,
            'canManageDetailKeys' => $this->canManageDetailKeys(),
        ]);
    }

    public function update(Request $request, string $uuid): RedirectResponse
    {
        $theme = $this->themeRepository->findUidWithRelations($uuid);

        if (!$theme) {
            return redirect()->route('error.404');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('themes', 'name')->ignore($theme->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', Rule::in(['0', '1'])],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'image_media_id' => ['nullable', 'integer', 'exists:medias,id'],
            'detail_keys' => ['nullable', 'array'],
            'detail_values' => ['nullable', 'array'],
        ]);

        $details = $this->extractDetails($request, $theme);

        $createdMediaIds = [];
        $storedPaths = [];

        try {
            DB::beginTransaction();

            if (($validated['is_default'] ?? '0') === '1') {
                $this->themeRepository->resetDefaultExcept($theme->id);
            }

            $this->themeRepository->update($theme->id, [
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_default' => $validated['is_default'] ?? '0',
                'image_id' => $this->resolveImageId($request, $theme, $createdMediaIds, $storedPaths),
            ]);

            $this->syncDetails($theme, $details);

            DB::commit();

            return redirect()->route('themes.index')->with('success', trans('common.success.update'));
        } catch (\Exception $e) {
            DB::rollBack();
            app(HelperController::class)->cleanupMedia($createdMediaIds, $storedPaths);
            $this->debugError($e);

            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function setDefault(string $uuid)
    {
        $theme = $this->themeRepository->findUid($uuid);

        if (!$theme) {
            return response()->json([
                'status' => false,
                'message' => trans('common.error.404'),
            ], 404);
        }

        try {
            DB::beginTransaction();

            $this->themeRepository->resetDefaultExcept($theme->id);
            $this->themeRepository->update($theme->id, [
                'is_default' => '1',
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => trans('common.success.update'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->debugErrorResJson($e);
        }
    }

    private function extractDetails(Request $request, Theme $theme): array
    {
        $keys = $request->input('detail_keys', []);
        $values = $request->input('detail_values', []);
        $details = [];
        $existingKeys = $theme->details()->pluck('key')->values()->all();
        $canManageKeys = $this->canManageDetailKeys();

        foreach ($keys as $index => $key) {
            $normalizedKey = Str::of((string) $key)->trim()->toString();
            $value = isset($values[$index]) ? trim((string) $values[$index]) : null;

            if ($normalizedKey === '' && ($value === null || $value === '')) {
                continue;
            }

            if ($normalizedKey === '') {
                throw ValidationException::withMessages([
                    "detail_keys.$index" => 'Key theme detail wajib diisi.',
                ]);
            }

            if (array_key_exists($normalizedKey, $details)) {
                throw ValidationException::withMessages([
                    "detail_keys.$index" => 'Key theme detail tidak boleh sama dalam theme yang sama.',
                ]);
            }

            if ($normalizedKey === 'background_theme_color' && !in_array($value, ['dark-mode', 'light-mode'], true)) {
                throw ValidationException::withMessages([
                    "detail_values.$index" => 'Value background_theme_color hanya boleh dark-mode atau light-mode.',
                ]);
            }

            $details[$normalizedKey] = $value;
        }

        if (!$canManageKeys) {
            $submittedKeys = array_keys($details);
            $sortedSubmitted = $submittedKeys;
            $sortedExisting = $existingKeys;
            sort($sortedSubmitted);
            sort($sortedExisting);

            if ($sortedSubmitted !== $sortedExisting) {
                throw ValidationException::withMessages([
                    'detail_keys' => 'Anda hanya dapat mengubah value theme detail yang sudah ada.',
                ]);
            }
        }

        return $details;
    }

    private function syncDetails(Theme $theme, array $details): void
    {
        $existingDetails = $theme->details()->get()->keyBy('key');
        $keys = array_keys($details);

        if (!empty($keys)) {
            $duplicateInDb = ThemeDetail::query()
                ->where('theme_id', $theme->id)
                ->whereIn('key', $keys)
                ->get()
                ->groupBy('key')
                ->first(fn ($items) => $items->count() > 1);

            if ($duplicateInDb) {
                throw ValidationException::withMessages([
                    'detail_keys' => 'Terdapat duplicate key pada theme detail untuk theme ini.',
                ]);
            }
        }

        $theme->details()
            ->whereNotIn('key', $keys ?: ['__empty__'])
            ->delete();

        foreach ($details as $key => $value) {
            /** @var ThemeDetail|null $existing */
            $existing = $existingDetails->get($key);

            ThemeDetail::query()->updateOrCreate(
                [
                    'theme_id' => $theme->id,
                    'key' => $key,
                ],
                [
                    'uuid' => $existing?->uuid ?? Str::uuid()->toString(),
                    'value' => $value,
                ]
            );
        }
    }

    private function resolveImageId(Request $request, Theme $theme, array &$createdMediaIds, array &$storedPaths): ?int
    {
        $file = $request->file('image');
        $selectedMediaId = $request->input('image_media_id');

        if ($file && $file->isValid()) {
            $stored = $this->storeImageFile($file);
            $createdMediaIds[] = $stored['media_id'];
            $storedPaths[] = $stored['relative_path'];

            return $stored['media_id'];
        }

        if ($selectedMediaId) {
            $media = $this->mediaRepository->find($selectedMediaId);

            if (!$media || $media->type !== 'image') {
                throw ValidationException::withMessages([
                    'image' => 'Media gambar tidak ditemukan atau bukan gambar.',
                ]);
            }

            return $media->id;
        }

        return $theme->image_id;
    }

    private function storeImageFile(UploadedFile $file): array
    {
        /** @var HelperController $helper */
        $helper = app(HelperController::class);
        $relativePath = $helper->uploadMediaFile($file, 'images', 'media');
        $dimensions = $helper->getImageDimensionsFromPath($relativePath, $file);

        $media = $this->mediaRepository->createFromUpload('image', $relativePath, [
            'extension' => $file->getClientOriginalExtension(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'original' => $file->getClientOriginalName(),
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
        ]);

        return [
            'media_id' => $media->id,
            'relative_path' => $relativePath,
        ];
    }

    private function canManageDetailKeys(): bool
    {
        return auth()->check() && auth()->user()->role?->category === 'master';
    }
}
