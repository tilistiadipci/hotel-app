<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportWilayahIndonesiaCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_imports_wilayah_from_json_and_can_be_run_repeatedly(): void
    {
        $source = tempnam(sys_get_temp_dir(), 'wilayah-test-');
        file_put_contents($source, json_encode([
            'Provinsi Test' => [
                'ID' => '99',
                'Kabupaten/Kota' => [
                    'Kota Test' => [
                        'ID' => '99.01',
                        'Type' => 'Kota',
                        'Kecamatan' => [
                            'Kecamatan Test' => [
                                'ID' => '99.01.01',
                                'Kelurahan/Desa' => [
                                    'Kelurahan Test' => [
                                        'ID' => '99.01.01.1001',
                                        'Kode Pos' => '99999',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        try {
            $this->artisan('wilayah:import', ['--source' => $source])
                ->expectsOutput('Master wilayah Indonesia berhasil diimpor.')
                ->assertSuccessful();

            $this->artisan('wilayah:import', ['--source' => $source])->assertSuccessful();

            $this->assertSame(1, DB::table('master_kelurahan_desa')->where('id', '99.01.01.1001')->count());
            $this->assertSame('99999', DB::table('master_kelurahan_desa')->where('id', '99.01.01.1001')->value('kode_pos'));
        } finally {
            @unlink($source);
        }
    }
}
