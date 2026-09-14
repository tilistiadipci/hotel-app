<?php

namespace Tests\Unit;

use App\Models\Hotel;
use App\Models\HotelConfiguration;
use App\Tenancy\HotelConfigurationManager;
use Tests\TestCase;

class HotelConfigurationManagerTest extends TestCase
{
    public function test_it_applies_hotel_storage_and_mqtt_configuration(): void
    {
        $hotel = new Hotel(['name' => 'Hotel Test']);
        $hotel->setRelation('configuration', new HotelConfiguration([
            'media_root' => '/hotel-test',
            'mqtt_host' => 'broker.hotel.test',
            'mqtt_port' => 1884,
            'mqtt_client_id' => 'hotel-test',
            'mqtt_username' => 'tenant-user',
            'mqtt_password' => 'tenant-secret',
            'mqtt_qos' => 2,
            'mqtt_tls' => true,
        ]));

        app(HotelConfigurationManager::class)->apply($hotel);

        $expectedRoot = rtrim(config('filesystems.media_base_root'), '/\\').DIRECTORY_SEPARATOR.'hotel-test';
        $this->assertSame($expectedRoot, config('filesystems.disks.media.root'));
        $this->assertSame('broker.hotel.test', config('mqtt-client.connections.default.host'));
        $this->assertSame(1884, config('mqtt-client.connections.default.port'));
        $this->assertSame('tenant-user', config('mqtt-client.connections.default.connection_settings.auth.username'));
        $this->assertSame('tenant-secret', config('mqtt-client.connections.default.connection_settings.auth.password'));
        $this->assertTrue(config('mqtt-client.connections.default.connection_settings.tls.enabled'));
    }
}
