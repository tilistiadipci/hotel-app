<?php

namespace App\Http\Controllers;

use App\Models\ManagerLicense;
use App\Models\MasterPaket;
use App\Models\Role;
use App\Models\User;
use App\Services\LicenseAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PlatformLicenseController extends Controller
{
    public function index()
    {
        $licenses = ManagerLicense::query()
            ->with(['manager.profile', 'masterPaket'])
            ->withSum(['usages as used_players' => fn ($query) => $query->where('status', 'active')], 'quantity')
            ->latest()
            ->get();

        return view('pages.platform.licenses.index', [
            'page' => 'platform-licenses',
            'icon' => 'fa fa-key',
            'licenses' => $licenses,
            'managers' => User::query()->withoutGlobalScope('hotel')
                ->whereHas('role', fn ($query) => $query->where('category', 'manager'))
                ->where('is_active', true)
                ->with('profile')
                ->orderBy('username')
                ->get(),
            'paket' => MasterPaket::query()->aktif()->orderBy('urutan')->orderBy('nama')->get(),
        ]);
    }

    public function store(Request $request, LicenseAssignmentService $service)
    {
        $data = $this->validateData($request);
        $paket = MasterPaket::query()->aktif()->findOrFail($data['master_paket_id']);
        [$startsAt, $expiresAt] = $service->datesFromRequest($data, $paket);

        ManagerLicense::query()->create([
            'manager_id' => $data['manager_id'],
            'master_paket_id' => $paket->id,
            'quantity_players' => $this->valueForPackage($paket, $data, 'quantity_players', 'maksimal_player'),
            'max_users' => $this->valueForPackage($paket, $data, 'max_users', 'maksimal_user'),
            'status' => $data['status'],
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'assigned_by' => $request->user()->id,
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('platform.licenses.index')->with('success', 'License manager berhasil dibuat.');
    }

    public function update(Request $request, ManagerLicense $license, LicenseAssignmentService $service)
    {
        $data = $this->validateData($request);
        $paket = MasterPaket::query()->aktif()->findOrFail($data['master_paket_id']);
        [$startsAt, $expiresAt] = $service->datesFromRequest($data, $paket);

        DB::transaction(function () use ($data, $license, $paket, $startsAt, $expiresAt, $service): void {
            $quantity = $this->valueForPackage($paket, $data, 'quantity_players', 'maksimal_player');
            if ($quantity !== null && $license->usedQuantity() > $quantity) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'quantity_players' => 'Jumlah license tidak boleh lebih kecil dari pemakaian saat ini.',
                ]);
            }

            $license->update([
                'manager_id' => $data['manager_id'],
                'master_paket_id' => $paket->id,
                'quantity_players' => $quantity,
                'max_users' => $this->valueForPackage($paket, $data, 'max_users', 'maksimal_user'),
                'status' => $data['status'],
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
                'notes' => $data['notes'] ?? null,
            ]);

            $license->load('usages.hotel');
            foreach ($license->usages->pluck('hotel')->filter()->unique('id') as $hotel) {
                $service->syncHotelLicense($hotel, $license);
            }
        });

        return redirect()->route('platform.licenses.index')->with('success', 'License manager berhasil diperbarui.');
    }

    public function destroy(ManagerLicense $license)
    {
        if ($license->usages()->exists()) {
            return back()->withErrors(['license' => 'License sudah dipakai manager dan tidak dapat dihapus. Ubah status menjadi suspended jika ingin menonaktifkan.']);
        }

        $license->delete();

        return redirect()->route('platform.licenses.index')->with('success', 'License manager berhasil dihapus.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'manager_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('role_id', Role::query()->where('category', 'manager')->value('id'))
                    ->whereNull('deleted_at')),
            ],
            'master_paket_id' => ['required', 'integer', Rule::exists('master_paket', 'id')->where('aktif', true)],
            'quantity_players' => ['nullable', 'integer', 'min:1'],
            'max_users' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', Rule::in([ManagerLicense::STATUS_ACTIVE, ManagerLicense::STATUS_SUSPENDED, ManagerLicense::STATUS_EXPIRED])],
            'starts_at' => ['nullable', 'date_format:d/m/Y'],
            'expires_at' => ['nullable', 'date_format:d/m/Y'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function valueForPackage(MasterPaket $paket, array $data, string $input, string $field): ?int
    {
        if ($paket->kode !== 'custom') {
            return $paket->{$field};
        }

        return filled($data[$input] ?? null) ? (int) $data[$input] : null;
    }
}
