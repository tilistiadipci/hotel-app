<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\MasterPaket;
use App\Models\TvChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MasterPaketController extends Controller
{
    public function index()
    {
        return view('pages.platform.master_paket.index', [
            'page' => 'master-paket',
            'icon' => 'fa fa-box-open',
            'paket' => MasterPaket::query()->withCount(['tvChannels', 'licenses'])->orderBy('urutan')->orderBy('nama')->get(),
        ]);
    }

    public function create()
    {
        return view('pages.platform.master_paket.create', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        DB::transaction(function () use ($data): void {
            if ($data['paket_default_registrasi']) {
                MasterPaket::query()->update(['paket_default_registrasi' => false]);
            }
            $paket = MasterPaket::query()->create($this->payload($data));
            $paket->tvChannels()->sync($data['channel_ids'] ?? []);
        });

        return redirect()->route('platform.master-paket.index')->with('success', 'Master paket berhasil ditambahkan.');
    }

    public function edit(MasterPaket $masterPaket)
    {
        return view('pages.platform.master_paket.edit', $this->formData($masterPaket));
    }

    public function update(Request $request, MasterPaket $masterPaket)
    {
        $request->merge(['kode' => $masterPaket->kode]);
        $data = $this->validateData($request, $masterPaket);

        DB::transaction(function () use ($data, $masterPaket): void {
            if ($data['paket_default_registrasi']) {
                MasterPaket::query()->where('id', '!=', $masterPaket->id)->update(['paket_default_registrasi' => false]);
            }
            $masterPaket->update($this->payload($data));
            $masterPaket->tvChannels()->sync($data['channel_ids'] ?? []);
        });

        return redirect()->route('platform.master-paket.index')->with('success', 'Master paket berhasil diperbarui.');
    }

    public function destroy(MasterPaket $masterPaket)
    {
        if ($masterPaket->paket_default_registrasi) {
            return back()->withErrors(['paket' => 'Paket default registrasi tidak dapat dihapus. Pilih paket default lain terlebih dahulu.']);
        }
        if ($masterPaket->licenses()->exists()) {
            return back()->withErrors(['paket' => 'Paket sudah dipakai lisensi hotel dan tidak dapat dihapus. Nonaktifkan paket jika tidak ingin dipakai lagi.']);
        }

        $masterPaket->delete();

        return redirect()->route('platform.master-paket.index')->with('success', 'Master paket berhasil dihapus.');
    }

    private function validateData(Request $request, ?MasterPaket $masterPaket = null): array
    {
        $request->merge([
            'kode' => Str::slug((string) $request->input('kode')),
            'aktif' => $request->boolean('aktif'),
            'paket_default_registrasi' => $request->boolean('paket_default_registrasi'),
        ]);

        $data = $request->validate([
            'kode' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9-]+$/', Rule::unique('master_paket', 'kode')->ignore($masterPaket?->id)],
            'nama' => ['required', 'string', 'max:120'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'durasi_hari' => ['nullable', 'integer', 'min:1', 'max:36500'],
            'maksimal_player' => ['nullable', 'integer', 'min:1'],
            'maksimal_user' => ['nullable', 'integer', 'min:1'],
            'urutan' => ['required', 'integer', 'min:0', 'max:65535'],
            'aktif' => ['required', 'boolean'],
            'paket_default_registrasi' => ['required', 'boolean'],
            'channel_ids' => ['nullable', 'array'],
            'channel_ids.*' => [
                'integer',
                Rule::exists('tv_channels', 'id')->where(fn ($query) => $query
                    ->where('hotel_id', Hotel::masterId())->where('is_active', true)->whereNull('deleted_at')),
            ],
        ]);

        if ($data['paket_default_registrasi'] && ! $data['aktif']) {
            throw ValidationException::withMessages(['aktif' => 'Paket default registrasi harus aktif.']);
        }
        if ($masterPaket?->paket_default_registrasi && ! $data['paket_default_registrasi']) {
            throw ValidationException::withMessages([
                'paket_default_registrasi' => 'Tetapkan paket lain sebagai default terlebih dahulu agar registrasi hotel tetap dapat diproses.',
            ]);
        }
        if ($data['paket_default_registrasi'] && empty($data['channel_ids'])) {
            throw ValidationException::withMessages(['channel_ids' => 'Paket default registrasi harus memiliki minimal satu TV channel.']);
        }

        return $data;
    }

    private function payload(array $data): array
    {
        return collect($data)->only([
            'kode', 'nama', 'deskripsi', 'durasi_hari', 'maksimal_player', 'maksimal_user',
            'paket_default_registrasi', 'aktif', 'urutan',
        ])->all();
    }

    private function formData(?MasterPaket $masterPaket = null): array
    {
        $channels = collect();
        if ($masterId = Hotel::masterId()) {
            $channels = TvChannel::query()->withoutGlobalScope('hotel')
                ->where('hotel_id', $masterId)->where('is_active', true)->whereNull('deleted_at')
                ->orderBy('group_title')->orderBy('sort_order')->orderBy('name')->get();
        }

        return [
            'page' => 'master-paket',
            'icon' => 'fa fa-box-open',
            'masterPaket' => $masterPaket,
            'channels' => $channels,
            'selectedChannelIds' => $masterPaket?->tvChannels()->pluck('tv_channels.id')->map(fn ($id) => (int) $id)->all() ?? [],
        ];
    }
}
