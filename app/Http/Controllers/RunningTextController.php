<?php

namespace App\Http\Controllers;

use App\Repositories\RunningTextGroupRepository;
use App\Repositories\RunningTextRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RunningTextController extends Controller
{
    protected RunningTextGroupRepository $groupRepository;
    protected RunningTextRepository $runningTextRepository;
    private string $page = 'running-texts';
    private string $icon = 'fa fa-align-left';

    public function __construct(RunningTextGroupRepository $groupRepository, RunningTextRepository $runningTextRepository)
    {
        $this->groupRepository = $groupRepository;
        $this->runningTextRepository = $runningTextRepository;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->groupRepository->getDatatable();
        }

        return view('pages.running_texts.index', [
            'page' => $this->page,
            'icon' => $this->icon,
        ]);
    }

    public function create()
    {
        return view('pages.running_texts.create', [
            'page' => $this->page,
            'icon' => $this->icon,
        ]);
    }

    public function store(Request $request)
    {
        try {
            $payload = $this->validateBatch($request);
            $groupData = $this->validateGroup($request);
            $this->applyRssSource($request, $groupData);

            DB::transaction(function () use ($groupData, $payload) {
                $group = $this->groupRepository->create($groupData);
                $this->storeBatch($payload, $group->id, $groupData);
            });
            return redirect()->route('running-texts.index')->with('success', trans('common.success.create'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function edit(string $uid)
    {
        $group = $this->groupRepository->findUid($uid);
        if (!$group) {
            return redirect()->route('error.404');
        }

        return view('pages.running_texts.edit', [
            'page' => $this->page,
            'icon' => $this->icon,
            'group' => $group->load('runningTexts'),
        ]);
    }

    public function update(Request $request, string $uid)
    {
        $group = $this->groupRepository->findUid($uid);
        if (!$group) {
            return redirect()->route('error.404');
        }

        $payload = $this->validateBatch($request);
        $groupData = $this->validateGroup($request);
        $this->applyRssSource($request, $groupData, $group);

        DB::transaction(function () use ($group, $groupData, $payload) {
            $this->groupRepository->updateByUid($group->uuid, $groupData);
            $this->runningTextRepository->query()
                ->where('running_text_group_id', $group->id)
                ->update([
                    'deleted_by' => auth()->id(),
                    'deleted_at' => now(),
                ]);
            $this->storeBatch($payload, $group->id, $groupData);
        });

        return redirect()->route('running-texts.index')->with('success', trans('common.success.update'));
    }

    public function show(Request $request, string $uid)
    {
        $group = $this->groupRepository->query()
            ->withCount('runningTexts')
            ->where('uuid', $uid)
            ->first();

        if ($request->ajax()) {
            if (!$group) {
                return response()->json([
                    'status' => false,
                    'message' => trans('common.error.404'),
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => view('pages.running_texts.info', [
                    'group' => $group,
                ])->render(),
                'return_type' => 'json',
            ]);
        }

        if (!$group) {
            return redirect()->route('error.404');
        }

        return redirect()->route('running-texts.index');
    }

    public function destroy(string $uid)
    {
        $group = $this->groupRepository->findUid($uid);
        if (!$group) {
            return response()->json([
                'status' => false,
                'message' => trans('common.error.404'),
            ]);
        }

        try {
            DB::transaction(function () use ($group) {
                $this->runningTextRepository->query()
                    ->where('running_text_group_id', $group->id)
                    ->update([
                        'deleted_by' => auth()->id(),
                        'deleted_at' => now(),
                    ]);
                $this->groupRepository->delete($group->uuid, null, false);
            });
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

    public function bulkDelete(Request $request)
    {
        try {
            $uids = $request->uids ?? [];
            if (!empty($uids)) {
                $groups = $this->groupRepository->query()->whereIn('uuid', $uids)->get();
                $ids = $groups->pluck('id')->all();
                if (!empty($ids)) {
                    $this->runningTextRepository->query()
                        ->whereIn('running_text_group_id', $ids)
                        ->update([
                            'deleted_by' => auth()->id(),
                            'deleted_at' => now(),
                        ]);
                }
            }

            $this->groupRepository->bulkDeleteByUid($request->uids ?? [], null, false);
            return response()->json([
                'status' => true,
                'message' => trans('common.success.delete'),
            ]);
        } catch (\Exception $e) {
            return $this->debugErrorResJson($e);
        }
    }

    public function previewRss(Request $request)
    {
        $request->validate([
            'rss_url' => ['nullable', 'url'],
            'rss_file' => ['nullable', 'file', 'mimes:xml,txt,rss'],
        ]);

        $xml = null;

        if ($request->filled('rss_url')) {
            try {
                $response = Http::withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0 Safari/537.36',
                        'Accept' => 'application/rss+xml, application/xml;q=0.9, text/xml;q=0.8, */*;q=0.5',
                        'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
                        'Referer' => $request->input('rss_url'),
                    ])
                    ->timeout(15)
                    ->get($request->input('rss_url'));
                if (!$response->successful()) {
                    throw new \Exception('RSS tidak ditemukan.');
                }
                $xml = $response->body();
            } catch (\Exception $e) {
                return response()->json([
                    'status' => false,
                    'error' => env('APP_DEBUG') ? $e->getMessage() : trans('common.error.500'),
                    'message' => 'Gagal mengambil RSS dari URL.',
                ]);
            }
        } elseif ($request->hasFile('rss_file')) {
            $file = $request->file('rss_file');
            if ($file && $file->isValid()) {
                $xml = $file->get();
            }
        }

        if (!$xml) {
            return response()->json([
                'status' => false,
                'message' => 'RSS tidak ditemukan.',
            ]);
        }

        $items = $this->parseRss($xml);

        return response()->json([
            'status' => true,
            'items' => $items,
        ]);
    }

    private function parseRss(string $xml): array
    {
        libxml_use_internal_errors(true);
        $feed = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (!$feed) {
            libxml_clear_errors();
            return [];
        }

        $items = [];

        if (isset($feed->channel->item)) {
            foreach ($feed->channel->item as $item) {
                $items[] = $this->mapRssItem($item);
            }
        } elseif (isset($feed->entry)) {
            foreach ($feed->entry as $entry) {
                $items[] = $this->mapRssItem($entry);
            }
        }

        return array_values(array_filter($items, function ($item) {
            return !empty($item['title']) && !empty($item['description']);
        }));
    }

    private function mapRssItem($item): array
    {
        $title = (string) ($item->title ?? '');
        $description = '';

        if (isset($item->description)) {
            $description = (string) $item->description;
        } elseif (isset($item->summary)) {
            $description = (string) $item->summary;
        } elseif (isset($item->content)) {
            $description = (string) $item->content;
        } elseif (isset($item->{'content:encoded'})) {
            $description = (string) $item->{'content:encoded'};
        }

        return [
            'title' => trim(strip_tags($title)),
            'description' => trim(strip_tags($description)),
        ];
    }

    private function validateGroup(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'rss_url' => ['nullable', 'url'],
            'rss_file' => ['nullable', 'file', 'mimes:xml,txt,rss'],
        ]);
    }

    private function validateBatch(Request $request): array
    {
        return $request->validate([
            'titles' => ['required', 'array', 'min:1'],
            'titles.*' => ['required', 'string', 'max:200'],
            'descriptions' => ['required', 'array', 'min:1'],
            'descriptions.*' => ['required', 'string'],
            'sort_orders' => ['required', 'array', 'min:1'],
            'sort_orders.*' => ['nullable', 'integer', 'min:0'],
            'is_actives' => ['required', 'array', 'min:1'],
            'is_actives.*' => ['in:0,1'],
        ]);
    }

    private function storeBatch(array $payload, int $groupId, array $groupData): void
    {
        $titles = $payload['titles'];
        $descriptions = $payload['descriptions'];
        $sortOrders = $payload['sort_orders'];
        $isActives = $payload['is_actives'];

        foreach ($titles as $i => $title) {
            $data = [
                'running_text_group_id' => $groupId,
                'title' => $title,
                'description' => $descriptions[$i] ?? '',
                'sort_order' => $sortOrders[$i] ?? 0,
                'is_active' => (int) ($isActives[$i] ?? 1) === 1,
            ];

            $this->runningTextRepository->create($data);
        }
    }

    private function applyRssSource(Request $request, array &$data, $group = null): void
    {
        if ($request->hasFile('rss_file')) {
            $file = $request->file('rss_file');
            if ($file && $file->isValid()) {
                $dir = storage_path('app/rss');
                File::ensureDirectoryExists($dir);
                $ext = $file->getClientOriginalExtension();
                $ext = $ext ? ('.' . $ext) : '';
                $filename = Str::uuid()->toString() . $ext;
                $file->move($dir, $filename);
                $path = 'rss/' . $filename;
                $data['link_rss_type'] = 'uploaded';
                $data['link_rss'] = $path;
                return;
            }
        }

        if ($request->filled('rss_url')) {
            $data['link_rss_type'] = 'link';
            $data['link_rss'] = $request->input('rss_url');
            return;
        }

        if ($group) {
            $data['link_rss_type'] = $group->link_rss_type;
            $data['link_rss'] = $group->link_rss;
        }
    }
}
