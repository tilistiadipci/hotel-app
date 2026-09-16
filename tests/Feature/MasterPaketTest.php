<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\MasterPaket;
use App\Models\TvChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class MasterPaketTest extends TestCase
{
    use DatabaseTransactions;

    public function test_superadmin_can_manage_package_and_its_channels(): void
    {
        $superadmin = User::query()->withoutGlobalScope('hotel')
            ->whereHas('role', fn ($query) => $query->whereIn('category', ['master', 'superadmin']))->firstOrFail();
        $channel = TvChannel::query()->withoutGlobalScope('hotel')
            ->where('hotel_id', Hotel::masterId())->where('is_active', true)->firstOrFail();
        $code = 'test-'.Str::lower(Str::random(6));

        $this->actingAs($superadmin)->post(route('platform.master-paket.store'), [
            'kode' => $code,
            'nama' => 'Paket Test',
            'deskripsi' => 'Paket untuk pengujian.',
            'durasi_hari' => 30,
            'maksimal_player' => 5,
            'maksimal_user' => 4,
            'urutan' => 10,
            'aktif' => 1,
            'paket_default_registrasi' => 0,
            'channel_ids' => [$channel->id],
        ])->assertRedirect(route('platform.master-paket.index'));

        $paket = MasterPaket::query()->where('kode', $code)->firstOrFail();
        $this->assertSame(30, $paket->durasi_hari);
        $this->assertTrue($paket->tvChannels()->whereKey($channel->id)->exists());
    }

    public function test_default_registration_package_requires_a_channel(): void
    {
        $superadmin = User::query()->withoutGlobalScope('hotel')
            ->whereHas('role', fn ($query) => $query->whereIn('category', ['master', 'superadmin']))->firstOrFail();

        $this->actingAs($superadmin)->post(route('platform.master-paket.store'), [
            'kode' => 'empty-default',
            'nama' => 'Default Kosong',
            'urutan' => 99,
            'aktif' => 1,
            'paket_default_registrasi' => 1,
        ])->assertSessionHasErrors('channel_ids');
    }
}
