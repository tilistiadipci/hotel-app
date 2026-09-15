<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Repositories\MediaRepository;
use App\Repositories\TVChannelRepository;
use App\Services\M3uPlaylistService;
use App\Services\TvChannelChangeNotifier;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TVChannelController extends Controller
{
    protected $channelRepository;

    protected MediaRepository $mediaRepository;

    protected TvChannelChangeNotifier $channelNotifier;

    private $page;

    private $icon = 'fa fa-tv';

    public function __construct(TVChannelRepository $channelRepository, MediaRepository $mediaRepository, TvChannelChangeNotifier $channelNotifier)
    {
        $this->channelRepository = $channelRepository;
        $this->mediaRepository = $mediaRepository;
        $this->channelNotifier = $channelNotifier;
        $this->page = 'tv channels';
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->channelRepository->getDatatable();
        }

        return view('pages.tv_channels.index', [
            'page' => $this->page,
            'icon' => $this->icon,
            'isMasterCatalog' => $this->isMasterCatalog(),
        ]);
    }

    public function create()
    {
        abort_unless($this->isMasterCatalog(), 403);

        return view('pages.tv_channels.create', [
            'page' => $this->page,
            'icon' => $this->icon,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($this->isMasterCatalog(), 403);
        $data = $this->validateRequest($request);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        $createdMediaIds = [];
        $storedPaths = [];

        try {
            DB::beginTransaction();

            $this->handleUploadLogo($request, $data, null, $createdMediaIds, $storedPaths);

            $this->channelRepository->create($data);

            DB::commit();

            return redirect()->route('tv-channels.index')->with('success', trans('common.success.create'));
        } catch (\Exception $e) {
            DB::rollBack();
            app(HelperController::class)->cleanupMedia($createdMediaIds, $storedPaths);
            $this->debugError($e);

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function edit(string $uid)
    {
        abort_unless($this->isMasterCatalog(), 403);
        $channel = $this->channelRepository->findUid($uid);
        if (! $channel) {
            return redirect()->route('error.404');
        }

        return view('pages.tv_channels.edit', [
            'page' => $this->page,
            'icon' => $this->icon,
            'channel' => $channel->load('imageMedia'),
        ]);
    }

    public function update(Request $request, string $uid)
    {
        abort_unless($this->isMasterCatalog(), 403);
        $data = $this->validateRequest($request, $uid);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $createdMediaIds = [];
        $storedPaths = [];

        try {
            DB::beginTransaction();

            $this->handleUploadLogo($request, $data, $uid, $createdMediaIds, $storedPaths);

            $channel = $this->channelRepository->updateByUid($uid, $data);

            DB::commit();

            if ($channel) {
                $this->channelNotifier->notifyHotels($this->channelNotifier->hotelIdsAssignedTo($channel->id));
            }

            return redirect()->route('tv-channels.index')->with('success', trans('common.success.update'));
        } catch (\Exception $e) {
            DB::rollBack();
            app(HelperController::class)->cleanupMedia($createdMediaIds, $storedPaths);
            $this->debugError($e);

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function destroy(string $uid)
    {
        abort_unless($this->isMasterCatalog(), 403);
        try {
            $channel = $this->channelRepository->findUid($uid);
            $affectedHotelIds = $channel ? $this->channelNotifier->hotelIdsAssignedTo($channel->id) : collect();

            $this->channelRepository->delete($uid);

            $this->channelNotifier->notifyHotels($affectedHotelIds);

            return response()->json([
                'status' => true,
                'message' => trans('common.success.delete'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => env('APP_DEBUG') ? $e->getMessage() : trans('common.error.500'),
            ]);
        }
    }

    public function show(Request $request, string $uid)
    {
        $channel = $this->channelRepository->findUid($uid);

        if ($request->ajax()) {
            if (! $channel) {
                return response()->json([
                    'status' => false,
                    'message' => trans('common.error.404'),
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => view('pages.tv_channels.info', [
                    'page' => $this->page,
                    'channel' => $channel->load('imageMedia'),
                    'isMasterCatalog' => $this->isMasterCatalog(),
                ])->render(),
                'return_type' => 'json',
            ]);
        }

        if (! $channel) {
            return redirect()->route('error.404');
        }

        return view('pages.tv_channels.show', [
            'page' => $this->page,
            'icon' => $this->icon,
            'channel' => $channel->load('imageMedia'),
            'isMasterCatalog' => $this->isMasterCatalog(),
        ]);
    }

    public function bulkDelete(Request $request)
    {
        abort_unless($this->isMasterCatalog(), 403);
        try {
            $this->channelRepository->bulkDeleteByUid($request->uids ?? []);

            return response()->json([
                'status' => true,
                'message' => trans('common.success.delete'),
            ]);
        } catch (\Exception $e) {
            return $this->debugErrorResJson($e);
        }
    }

    public function importForm()
    {
        abort_unless($this->isMasterCatalog(), 403);

        return view('pages.tv_channels.import', ['page' => $this->page, 'icon' => $this->icon]);
    }

    public function importPreview(Request $request, M3uPlaylistService $playlist)
    {
        abort_unless($this->isMasterCatalog(), 403);
        $file = $request->validate(['playlist' => ['required', 'file', 'max:10240']])['playlist'];
        abort_unless(in_array(Str::lower($file->getClientOriginalExtension()), ['m3u', 'm3u8'], true), 422, __('platform.tv_catalog.invalid_extension'));

        $token = (string) Str::uuid();
        $directory = storage_path('app/m3u-imports');
        File::ensureDirectoryExists($directory);
        $movedFile = $file->move($directory, $token.'.'.$file->getClientOriginalExtension());

        try {
            $contents = File::get($movedFile->getPathname());
            $channels = $playlist->parse($contents);
        } finally {
            File::delete($movedFile->getPathname());
        }

        if (empty($channels)) {
            throw ValidationException::withMessages(['playlist' => __('platform.tv_catalog.no_channels_found')]);
        }
        $channels = $playlist->markExisting($channels, Hotel::masterId());

        if ($request->boolean('direct_import')) {
            $result = $playlist->import($channels, Hotel::masterId());

            return redirect()->route('tv-channels.index')
                ->with('success', __('platform.tv_catalog.import_done', $result));
        }

        Storage::disk('local')->put('m3u-imports/'.$token.'.json', json_encode($channels, JSON_UNESCAPED_SLASHES));
        $request->session()->put('m3u_import_token', $token);

        return redirect()->route('tv-channels.import.preview.show', $token);
    }

    public function staleImportPreview()
    {
        return redirect()->route('tv-channels.import')
            ->with('warning', 'Silakan pilih kembali file playlist untuk membuat preview import.');
    }

    public function showImportPreview(Request $request, string $token)
    {
        abort_unless($this->isMasterCatalog(), 403);

        $sessionToken = (string) $request->session()->get('m3u_import_token');
        $path = 'm3u-imports/'.$token.'.json';

        if (! Str::isUuid($token) || ! hash_equals($sessionToken, $token) || ! Storage::disk('local')->exists($path)) {
            return redirect()->route('tv-channels.import')
                ->with('error', 'Preview import sudah tidak tersedia. Silakan upload kembali file playlist.');
        }

        $channels = json_decode(Storage::disk('local')->get($path), true);
        if (! is_array($channels)) {
            return redirect()->route('tv-channels.import')
                ->with('error', 'Data preview tidak valid. Silakan upload kembali file playlist.');
        }

        return view('pages.tv_channels.import-preview', compact('channels', 'token') + [
            'page' => $this->page,
            'icon' => $this->icon,
        ]);
    }

    public function importStore(Request $request, M3uPlaylistService $playlist)
    {
        abort_unless($this->isMasterCatalog(), 403);
        $validated = $request->validate([
            'token' => ['required', 'uuid'],
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['required', 'string', 'size:64'],
        ]);
        $token = $validated['token'];
        abort_unless(hash_equals((string) $request->session()->pull('m3u_import_token'), $token), 419);
        $path = 'm3u-imports/'.$token.'.json';
        abort_unless(Storage::disk('local')->exists($path), 419);
        $channels = json_decode(Storage::disk('local')->get($path), true);
        $selected = array_fill_keys($validated['selected'], true);
        $channels = array_values(array_filter(
            is_array($channels) ? $channels : [],
            fn (array $channel) => isset($selected[$channel['source_hash'] ?? ''])
        ));

        if (empty($channels)) {
            throw ValidationException::withMessages([
                'selected' => 'Pilih minimal satu channel yang akan dimasukkan ke database.',
            ]);
        }

        Storage::disk('local')->delete($path);
        $result = $playlist->import($channels, Hotel::masterId());

        return redirect()->route('tv-channels.index')->with('success', __('platform.tv_catalog.import_done', $result));
    }

    public function updateAssignment(Request $request, string $uid)
    {
        abort_if($this->isMasterCatalog(), 403);
        $hotelId = app(TenantContext::class)->id();
        $channel = \App\Models\TvChannel::query()->withoutGlobalScope('hotel')->where('uuid', $uid)->whereNull('deleted_at')->firstOrFail();
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'custom_name' => ['nullable', 'string', 'max:150'],
            'custom_type' => ['nullable', Rule::in(['digital', 'streaming'])],
            'custom_region' => ['nullable', Rule::in(['national', 'international'])],
            'custom_stream_url' => ['nullable', 'string', 'max:5000'],
            'custom_frequency' => ['nullable', 'string', 'max:60'],
            'custom_quality' => ['nullable', 'string', 'max:20'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:1024'],
            'remove_custom_image' => ['nullable', 'boolean'],
        ]);

        $assignment = DB::table('hotel_tv_channel')
            ->where('hotel_id', $hotelId)
            ->where('tv_channel_id', $channel->id)
            ->first();
        abort_unless($assignment, 404);

        $customImageId = $assignment->custom_image_id;
        $createdMediaIds = [];
        $storedPaths = [];

        try {
            if ($request->boolean('remove_custom_image')) {
                $customImageId = null;
            }

            if ($request->hasFile('image')) {
                $stored = $this->storeImageFile($request->file('image'));
                $customImageId = $stored['media_id'];
                $createdMediaIds[] = $stored['media_id'];
                $storedPaths[] = $stored['relative_path'];
            }

            $updated = DB::table('hotel_tv_channel')->where('hotel_id', $hotelId)->where('tv_channel_id', $channel->id)->update([
                'is_active' => $data['is_active'],
                'sort_order' => $data['sort_order'] ?? 0,
                'custom_name' => filled($data['custom_name'] ?? null) ? $data['custom_name'] : null,
                'custom_type' => $data['custom_type'] ?? null,
                'custom_region' => $data['custom_region'] ?? null,
                'custom_stream_url' => filled($data['custom_stream_url'] ?? null) ? $data['custom_stream_url'] : null,
                'custom_frequency' => filled($data['custom_frequency'] ?? null) ? $data['custom_frequency'] : null,
                'custom_quality' => filled($data['custom_quality'] ?? null) ? $data['custom_quality'] : null,
                'custom_image_id' => $customImageId,
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            app(HelperController::class)->cleanupMedia($createdMediaIds, $storedPaths);
            throw $e;
        }

        abort_unless($updated || DB::table('hotel_tv_channel')->where('hotel_id', $hotelId)->where('tv_channel_id', $channel->id)->exists(), 404);

        $this->channelNotifier->notifyHotel($hotelId);

        return back()->with('success', __('platform.tv_catalog.channel_saved'));
    }

    public function editAssignment(string $uid)
    {
        abort_if($this->isMasterCatalog(), 403);
        $channel = $this->channelRepository->findUid($uid);
        abort_unless($channel, 404);

        return view('pages.tv_channels.assignment-edit', [
            'page' => $this->page,
            'icon' => $this->icon,
            'channel' => $channel->load(['imageMedia', 'hotelImageMedia']),
        ]);
    }

    private function isMasterCatalog(): bool
    {
        return (bool) app(TenantContext::class)->hotel()?->is_system;
    }

    private function validateRequest(Request $request, $uid = null): array
    {
        $channelId = null;
        if ($uid) {
            $channelId = optional($this->channelRepository->findUid($uid))->id;
        }

        $rules = [
            'name' => 'required|max:150',
            'slug' => [
                'nullable',
                'max:180',
                uniqueNotDeleted('tv_channels', 'slug', $channelId),
            ],
            'type' => 'required|in:digital,streaming',
            'region' => 'required|in:national,international',
            'stream_url' => 'nullable|max:5000',
            'frequency' => 'nullable|max:60',
            'quality' => 'nullable|max:20',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'required|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:1024',
            'image_media_id' => 'nullable|integer|exists:medias,id',
        ];

        return $request->validate($rules);
    }

    private function handleUploadLogo(Request $request, array &$data, ?string $uid = null, array &$createdMediaIds = [], array &$storedPaths = []): void
    {
        $file = $request->file('image');
        $selectedMediaId = $request->input('image_media_id');
        $existing = $uid ? $this->channelRepository->findUid($uid) : null;

        if ($file && $file->isValid()) {
            $stored = $this->storeImageFile($file);
            $data['image_id'] = $stored['media_id'];
            $createdMediaIds[] = $stored['media_id'];
            $storedPaths[] = $stored['relative_path'];
        } elseif ($selectedMediaId) {
            $media = $this->mediaRepository->find($selectedMediaId);
            if (! $media || $media->type !== 'image') {
                throw ValidationException::withMessages([
                    'image' => 'Media gambar tidak ditemukan atau bukan gambar.',
                ]);
            }
            $data['image_id'] = $media->id;
        } elseif ($existing) {
            $data['image_id'] = $existing->image_id;
        }
    }

    private function storeImageFile(UploadedFile $file): array
    {
        if (! $file->isValid()) {
            throw new \Exception('File gambar tidak valid.');
        }

        /** @var HelperController $helper */
        $helper = app(HelperController::class);
        $relativePath = $helper->uploadMediaFile($file, 'images', 'media');
        if (empty($relativePath)) {
            throw new \Exception('Gagal menentukan path penyimpanan gambar.');
        }

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
}
