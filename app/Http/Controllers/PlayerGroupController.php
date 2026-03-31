<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\PlayerGroupRepository;

class PlayerGroupController extends Controller
{
    protected PlayerGroupRepository $playerGroupRepository;
    private string $page = 'player-groups';
    private string $icon = 'fa fa-users';

    public function __construct(PlayerGroupRepository $playerGroupRepository)
    {
        $this->playerGroupRepository = $playerGroupRepository;
    }


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->playerGroupRepository->getDatatable();
        }

        return view('pages.player_groups.index', [
            'page' => $this->page,
            'icon' => $this->icon,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.player_groups.create', [
            'page' => $this->page,
            'icon' => $this->icon,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $data = $this->validateRequest($request);
            $this->playerGroupRepository->create($data);

            return redirect()->route('player-groups.index')->with('success', trans('common.success.create'));

        } catch (\Exception $e) {
            $this->debugError($e);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $uid)
    {
        $playerGroup = $this->playerGroupRepository->findUid($uid);

        if ($request->ajax()) {
            if (!$playerGroup) {
                return response()->json([
                    'status' => false,
                    'message' => trans('common.error.404'),
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => view('pages.player_groups.info', [
                    'playerGroup' => $playerGroup->load('players'),
                ])->render(),
                'return_type' => 'json',
            ]);
        }

        if (!$playerGroup) {
            return redirect()->route('error.404');
        }

        return redirect()->route('player-groups.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $uid)
    {
        $playerGroup = $this->playerGroupRepository->findUid($uid);
        if (!$playerGroup) {
            return redirect()->route('error.404');
        }

        return view('pages.player_groups.edit', [
            'page' => $this->page,
            'icon' => $this->icon,
            'playerGroup' => $playerGroup
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $data = $this->validateRequest($request, $id);
            $this->playerGroupRepository->updateByUid($id, $data);

            return redirect()->route('player-groups.index')->with('success', trans('common.success.update'));
        } catch (\Exception $e) {
            $this->debugError($e);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uid)
    {
        try {
            $this->playerGroupRepository->delete($uid);

            return redirect()->route('player-groups.index')->with('success', trans('common.success.delete'));
        } catch (\Exception $e) {
            $this->debugError($e);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    private function validateRequest(Request $request, ?string $uid = null): array
    {
        $playerId = null;
        if ($uid) {
            $playerId = optional($this->playerGroupRepository->findUid($uid))->id;
        }

        $rules = [
            'name' => 'required|string|max:150',
            'is_active' => 'nullable|boolean',
        ];

        $data = $request->validate($rules);
        $data['is_active'] = $data['is_active'] ?? true;

        return $data;
    }
}
