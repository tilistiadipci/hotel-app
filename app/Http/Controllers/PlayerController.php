<?php

namespace App\Http\Controllers;

use App\Repositories\PlayerRepository;
use App\Repositories\ThemeRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlayerController extends Controller
{
    protected PlayerRepository $playerRepository;
    protected ThemeRepository $themeRepository;
    private string $page = 'players';
    private string $icon = 'fa fa-users';

    public function __construct(PlayerRepository $playerRepository, ThemeRepository $themeRepository)
    {
        $this->playerRepository = $playerRepository;
        $this->themeRepository = $themeRepository;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->playerRepository->getDatatable();
        }

        return view('pages.players.index', [
            'page' => $this->page,
            'icon' => $this->icon,
        ]);
    }

    public function create()
    {
        return view('pages.players.create', [
            'page' => $this->page,
            'icon' => $this->icon,
            'themes' => $this->themeRepository->getList(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);

        try {
            DB::beginTransaction();
            $this->playerRepository->create($data);
            DB::commit();

            return redirect()->route('players.index')->with('success', trans('common.success.create'));
        } catch (\Exception $e) {
            DB::rollBack();
            $this->debugError($e);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function show(Request $request, string $uid)
    {
        $player = $this->playerRepository->findUid($uid);

        if ($request->ajax()) {
            if (!$player) {
                return response()->json([
                    'status' => false,
                    'message' => trans('common.error.404'),
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => view('pages.players.info', [
                    'player' => $player,
                ])->render(),
                'return_type' => 'json',
            ]);
        }

        if (!$player) {
            return redirect()->route('error.404');
        }

        return redirect()->route('players.index');
    }

    public function edit(string $uid)
    {
        $player = $this->playerRepository->findUid($uid);
        if (!$player) {
            return redirect()->route('error.404');
        }

        return view('pages.players.edit', [
            'page' => $this->page,
            'icon' => $this->icon,
            'player' => $player,
            'themes' => $this->themeRepository->getList(),
        ]);
    }

    public function update(Request $request, string $uid)
    {
        $data = $this->validateRequest($request, $uid);

        try {
            DB::transaction(function () use ($uid, $data) {
                $player = $this->playerRepository->findUidForUpdate($uid);
                if (!$player) {
                    throw new \RuntimeException(trans('common.error.404'));
                }

                if ((int) ($data['is_active'] ?? 1) === 0 && $this->playerRepository->hasActiveBooking($player->id)) {
                    throw ValidationException::withMessages([
                        'is_active' => trans('common.player.cannot_deactivate_when_booked'),
                    ]);
                }

                $this->playerRepository->updateByUid($uid, $data);
            });

            return redirect()->route('players.index')->with('success', trans('common.success.update'));
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            $this->debugError($e);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function destroy(string $uid)
    {
        try {
            $this->playerRepository->delete($uid);

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
            $this->playerRepository->bulkDeleteByUid($request->uids ?? []);

            return response()->json([
                'status' => true,
                'message' => trans('common.success.delete'),
            ]);
        } catch (\Exception $e) {
            return $this->debugErrorResJson($e);
        }
    }

    private function validateRequest(Request $request, ?string $uid = null): array
    {
        $playerId = null;
        if ($uid) {
            $playerId = optional($this->playerRepository->findUid($uid))->id;
        }

        $rules = [
            'name' => 'required|string|max:150',
            'alias' => 'required|string|max:100',
            'serial' => 'required|string|max:100|unique:players,serial' . ($playerId ? ',' . $playerId : ''),
            'theme_id' => 'required|integer|exists:themes,id',
            'is_active' => 'nullable|boolean',
        ];

        $data = $request->validate($rules);
        $data['is_active'] = $data['is_active'] ?? true;

        return $data;
    }
}
