<?php

namespace App\Http\Controllers;

use App\Http\Controllers\HelperController;
use App\Repositories\TVChannelRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TVChannelController extends Controller
{
    protected $channelRepository;
    private $page;
    private $icon = 'fa fa-tv';

    public function __construct(TVChannelRepository $channelRepository)
    {
        $this->channelRepository = $channelRepository;
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

        try {
            DB::beginTransaction();
            $this->handleUploadLogo($request, $data);

            $this->channelRepository->create($data);

            DB::commit();
            return redirect()->route('tv-channels.index')->with('success', trans('common.success.create'));
        } catch (\Exception $e) {
            DB::rollBack();
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
            'channel' => $channel,
        ]);
    }

    public function update(Request $request, string $uid)
    {
        $data = $this->validateRequest($request, $uid);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        try {
            DB::beginTransaction();
            $this->handleUploadLogo($request, $data);

            $this->channelRepository->updateByUid($uid, $data);

            DB::commit();
            return redirect()->route('tv-channels.index')->with('success', trans('common.success.update'));
        } catch (\Exception $e) {
            DB::rollBack();
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
                    'channel' => $channel,
                ])->render(),
                'return_type' => 'json',
            ]);
        }

        if (!$channel) {
            return redirect()->route('error.404');
        }

        return view('pages.tv_channels.show', [
            'page' => $this->page,
            'channel' => $channel,
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

    private function handleUploadLogo(Request $request, array &$data): void
    {
        if ($request->hasFile('logo')) {
            app(HelperController::class)->storeImage($request, 'logo', 'tv_channels', 'logo');
            $data['logo'] = $request->input('logo');
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
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:1024',
        ];

        return $request->validate($rules);
    }
}
