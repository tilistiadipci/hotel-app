<?php

namespace App\Http\Controllers;

use App\Http\Controllers\HelperController;
use App\Repositories\MediaRepository;
use App\Repositories\TVChannelRepository;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TVChannelController extends Controller
{
    protected $channelRepository;
    protected MediaRepository $mediaRepository;
    private $page;
    private $icon = 'fa fa-tv';

    public function __construct(TVChannelRepository $channelRepository, MediaRepository $mediaRepository)
    {
        $this->channelRepository = $channelRepository;
        $this->mediaRepository = $mediaRepository;
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
        ]);
    }

    public function create()
    {
        return view('pages.tv_channels.create', [
            'page' => $this->page,
            'icon' => $this->icon,
        ]);
    }

    public function store(Request $request)
    {
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
        $channel = $this->channelRepository->findUid($uid);
        if (!$channel) {
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
        $data = $this->validateRequest($request, $uid);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $createdMediaIds = [];
        $storedPaths = [];

        try {
            DB::beginTransaction();

            $this->handleUploadLogo($request, $data, $uid, $createdMediaIds, $storedPaths);

            $this->channelRepository->updateByUid($uid, $data);

            DB::commit();
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
        try {
            $this->channelRepository->delete($uid);

            return response()->json([
                'status' => true,
                'message' => trans('common.success.delete')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => env('APP_DEBUG') ? $e->getMessage() : trans('common.error.500')
            ]);
        }
    }

    public function show(Request $request, string $uid)
    {
        $channel = $this->channelRepository->findUid($uid);

        if ($request->ajax()) {
            if (!$channel) {
                return response()->json([
                    'status' => false,
                    'message' => trans('common.error.404')
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => view('pages.tv_channels.info', [
                    'page' => $this->page,
                    'channel' => $channel->load('imageMedia'),
                ])->render(),
                'return_type' => 'json',
            ]);
        }

        if (!$channel) {
            return redirect()->route('error.404');
        }

        return view('pages.tv_channels.show', [
            'page' => $this->page,
            'channel' => $channel->load('imageMedia'),
        ]);
    }

    public function bulkDelete(Request $request)
    {
        try {
            $this->channelRepository->bulkDeleteByUid($request->uids ?? []);

            return response()->json([
                'status' => true,
                'message' => trans('common.success.delete')
            ]);
        } catch (\Exception $e) {
            return $this->debugErrorResJson($e);
        }
    }

    private function validateRequest(Request $request, $uid = null): array
    {
        $channelId = null;
        if ($uid) {
            $channelId = optional($this->channelRepository->findUid($uid))->id;
        }

        $rules = [
            'name' => 'required|max:150',
            'slug' => 'nullable|max:180|unique:tv_channels,slug' . ($channelId ? ',' . $channelId : ''),
            'type' => 'required|in:digital,streaming',
            'region' => 'required|in:national,international',
            'stream_url' => 'nullable|max:255',
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
            if (!$media || $media->type !== 'image') {
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
        if (!$file->isValid()) {
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
