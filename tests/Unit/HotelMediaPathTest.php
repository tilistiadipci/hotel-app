<?php

namespace Tests\Unit;

use App\Models\Hotel;
use App\Tenancy\HotelMediaPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use InvalidArgumentException;
use Tests\TestCase;

class HotelMediaPathTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_generates_a_unique_relative_folder_from_the_hotel_name(): void
    {
        $hotel = Hotel::query()->create([
            'code' => 'MEDIA-A',
            'name' => 'Hotel Pantai Indah',
            'slug' => 'hotel-pantai-indah-test',
            'timezone' => 'Asia/Jakarta',
            'locale' => 'id_ID',
            'currency' => 'IDR',
            'status' => 'active',
            'is_active' => true,
        ]);
        $hotel->configuration()->create([
            'media_root' => '/hotel-pantai-indah',
            'mqtt_port' => 1883,
        ]);

        $this->assertSame('/hotel-pantai-indah-2', app(HotelMediaPath::class)->uniqueRoot('Hotel Pantai Indah'));
    }

    public function test_it_rejects_an_absolute_or_traversing_hotel_folder(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(HotelMediaPath::class)->absoluteRoot('../hotel-lain');
    }
}
