<?php

namespace App\Http\Controllers;

use App\Models\Warning as WarningModel;
use App\Repositories\PlayerGroupRepository;
use App\Repositories\PlayerRepository;
use App\Repositories\WarningRepository;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class WarningController extends Controller
{
    protected WarningRepository $warningRepository;
    protected PlayerRepository $playerRepository;
    protected PlayerGroupRepository $playerGroupRepository;
    private string $page = 'warnings';
    private string $icon = 'fa fa-exclamation-triangle';

    public function __construct(
        WarningRepository $warningRepository,
        PlayerRepository $playerRepository,
        PlayerGroupRepository $playerGroupRepository
    ) {
        $this->warningRepository = $warningRepository;
        $this->playerRepository = $playerRepository;
        $this->playerGroupRepository = $playerGroupRepository;
    }

    public function index()
    {
        return view('pages.warnings.create', $this->getCreateViewData());
    }

    public function create()
    {
        return view('pages.warnings.create', $this->getCreateViewData());
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);
        $data['message'] = $data['message'] ?? '';
        $data['issued_at'] = $this->resolveIssuedAt($data);
        $data['expires_at'] = null;

        try {
            $warnings = DB::transaction(function () use ($data) {
                return $this->warningRepository->createForTargets($data);
            });

            if ($warnings->isEmpty()) {
                return redirect()->back()->withInput()->with('warning', 'Tidak ada TV aktif yang cocok dengan target yang dipilih.');
            }

            $this->publishWarningPayload($warnings);

            $firebase = new FirebaseService();
            foreach ($warnings as $warning) {

                $topic = 'warning_player_' . $warning->player->serial;

                try {
                    $result = $firebase->sendToTopic($topic, [
                        'type' => $request->type == 'other' ? $request->other_type : $request->type,
                        'priority' => $request->priority,
                        'message' => $request->message,
                        'schedule_mode' => $request->schedule_mode,
                        'triggered_at' => now()->toISOString(),
                    ]);

                    Log::info("FCM sent to topic {$topic}", ['result' => $result]);
                } catch (\Exception $e) {
                    Log::error("FCM ERROR: " . $e->getMessage());
                }
            }

            return redirect()
                ->route('warnings.create')
                ->with('success', 'Warning berhasil dikirim ke ' . $warnings->count() . ' TV target.');
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->errors());
        } catch (\Exception $e) {
            $this->debugError($e);
            return redirect()->back()->withInput()->with('error', "Internal server error");
        }
    }

    private function getCreateViewData(): array
    {
        $playerGroups = $this->playerGroupRepository->query()
            ->withCount('players')
            ->get();

        $players = $this->playerRepository->query()
            ->with('currentBooking')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return [
            'page' => $this->page,
            'icon' => $this->icon,
            'warningTypes' => WarningModel::getTypes(),
            'priorities' => WarningModel::getPriorities(),
            'playerGroups' => $playerGroups,
            'players' => $players,
            'playerCount' => $players->count(),
        ];
    }

    private function validateRequest(Request $request): array
    {
        $data = $request->validate([
            'type' => ['required', 'in:' . implode(',', WarningModel::TYPE_KEYS)],
            'other_type' => ['nullable', 'string', 'max:120'],
            'priority' => ['required', 'in:' . implode(',', WarningModel::PRIORITY_KEYS)],
            'target_mode' => ['required', 'in:all,groups,players'],
            'target_group_ids' => ['nullable', 'array'],
            'target_group_ids.*' => ['integer', 'exists:player_groups,id'],
            'target_player_ids' => ['nullable', 'array'],
            'target_player_ids.*' => ['integer', 'exists:players,id'],
            'schedule_mode' => ['required', 'in:now,plus_5'],
        ], [
            'message.required' => 'Pesan warning wajib diisi.',
            'target_mode.required' => 'Silakan pilih target warning.',
        ]);

        if ($data['target_mode'] === 'groups' && empty($data['target_group_ids'])) {
            throw ValidationException::withMessages([
                'target_group_ids' => 'Pilih minimal satu player group.',
            ]);
        }

        if ($data['target_mode'] === 'players' && empty($data['target_player_ids'])) {
            throw ValidationException::withMessages([
                'target_player_ids' => 'Pilih minimal satu TV/player.',
            ]);
        }

        if ($data['type'] === 'other' && empty($data['other_type'])) {
            throw ValidationException::withMessages([
                'other_type' => 'Mohon isi jenis bencana lainnya.',
            ]);
        }

        return $data;
    }

    private function resolveIssuedAt(array $data)
    {
        return match ($data['schedule_mode']) {
            'plus_5' => now()->addMinutes(5),
            default => now(),
        };
    }

    private function publishWarningPayload($warnings): void
    {
        Log::info('Warning payload prepared for websocket broadcast.', [
            'count' => $warnings->count(),
            'warning_ids' => $warnings->pluck('id')->all(),
            'players' => $warnings->pluck('serial')->all(),
        ]);
    }
}
