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

            return redirect()->back()->with('success', trans('common.success.update'));
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
        $existingKeys = $theme->details()
            ->pluck('key')
            ->reject(fn ($key) => $this->isDeprecatedDetailKey((string) $key))
            ->values()
            ->all();
        $allowedReadonlyKeys = $this->getReadonlyAllowedDetailKeys($theme, $existingKeys);
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

            if ($this->isDeprecatedDetailKey($normalizedKey)) {
                continue;
            }

            if ($this->isImageDetailKey($normalizedKey) && $value !== null && $value !== '') {
                $imageMediaIds = $this->extractImageMediaIds($value);

                if (empty($imageMediaIds)) {
                    throw ValidationException::withMessages([
                        "detail_values.$index" => "Value {$normalizedKey} harus berupa image media id atau daftar image media id yang valid.",
                    ]);
                }

                if ($this->isSingleImageDetailKey($normalizedKey) && count($imageMediaIds) > 1) {
                    throw ValidationException::withMessages([
                        "detail_values.$index" => "Value {$normalizedKey} hanya boleh berisi satu image media id.",
                    ]);
                }

                foreach ($imageMediaIds as $mediaId) {
                    $media = $this->mediaRepository->find($mediaId);
                    if (!$media || $media->type !== 'image') {
                        throw ValidationException::withMessages([
                            "detail_values.$index" => "Value {$normalizedKey} harus berupa media image yang valid.",
                        ]);
                    }
                }
            }

            $details[$normalizedKey] = $this->normalizeDetailValue($normalizedKey, $value);
        }

        if (!$canManageKeys) {
            $submittedKeys = array_keys($details);
            $sortedSubmitted = $submittedKeys;
            $sortedExisting = $allowedReadonlyKeys;
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

    private function normalizeDetailValue(string $key, ?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($this->isImageDetailKey($key)) {
            $imageMediaIds = $this->extractImageMediaIds($value);

            if (empty($imageMediaIds)) {
                return null;
            }

            if ($this->isSingleImageDetailKey($key)) {
                return (string) $imageMediaIds[0];
            }

            if (count($imageMediaIds) === 1) {
                return (string) $imageMediaIds[0];
            }

            return json_encode(array_map('strval', $imageMediaIds), JSON_UNESCAPED_SLASHES);
        }

        if ($this->isMultilineDetailKey($key)) {
            return preg_replace('/\r\n|\r|\n/', '<br>', $value);
        }

        return $value;
    }

    private function isMultilineDetailKey(string $key): bool
    {
        return in_array($key, ['running_text', 'marquee_text'], true);
    }

    private function isDeprecatedDetailKey(string $key): bool
    {
        return $key === 'background_theme_color';
    }

    private function isImageDetailKey(string $key): bool
    {
        return preg_match('/^image(_id)?_\d+$/', $key) === 1;
    }

    private function isSingleImageDetailKey(string $key): bool
    {
        return preg_match('/^image(_id)?_3$/', $key) === 1;
    }

    private function extractImageMediaIds(?string $value): array
    {
        $normalizedValue = trim((string) $value);

        if ($normalizedValue === '') {
            return [];
        }

        if (ctype_digit($normalizedValue)) {
            return [(int) $normalizedValue];
        }

        $decoded = json_decode($normalizedValue, true);
        if (!is_array($decoded)) {
            return [];
        }

        $mediaIds = collect($decoded)
            ->map(fn ($item) => trim((string) $item))
            ->filter(fn ($item) => $item !== '' && ctype_digit($item))
            ->map(fn ($item) => (int) $item)
            ->unique()
            ->values()
            ->all();

        return $mediaIds;
    }

    private function getReadonlyAllowedDetailKeys(Theme $theme, array $existingKeys = []): array
    {
        $keys = $existingKeys ?: $theme->details()
            ->pluck('key')
            ->reject(fn ($key) => $this->isDeprecatedDetailKey((string) $key))
            ->values()
            ->all();

        return collect($keys)
            ->merge($this->getFixedDetailKeys($theme))
            ->unique()
            ->values()
            ->all();
    }

    private function getFixedDetailKeys(Theme $theme): array
    {
        if (!$this->isDefaultThemeByName($theme)) {
            return [];
        }

        return [
            'image_id_1',
            'image_id_2',
            'image_id_3',
        ];
    }

    private function isDefaultThemeByName(Theme $theme): bool
    {
        return strcasecmp((string) $theme->name, 'Default Theme') === 0;
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
