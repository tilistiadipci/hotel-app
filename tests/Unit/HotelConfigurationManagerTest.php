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
            'use_custom_mqtt' => true,
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

    public function test_it_uses_environment_mqtt_when_hotel_override_is_disabled(): void
    {
        config(['mqtt-client.environment_defaults' => [
            'host' => 'broker.env.test',
            'port' => 1883,
            'client_id' => 'env-client',
            'username' => 'env-user',
            'password' => 'env-secret',
            'qos' => 1,
            'tls' => false,
        ]]);
        $hotel = new Hotel(['name' => 'Hotel Env']);
        $hotel->setRelation('configuration', new HotelConfiguration([
            'media_root' => '/hotel-env',
            'use_custom_mqtt' => false,
            'mqtt_host' => 'broker.hotel.test',
            'mqtt_port' => 2883,
            'mqtt_client_id' => 'hotel-client',
            'mqtt_username' => 'hotel-user',
            'mqtt_password' => 'hotel-secret',
            'mqtt_qos' => 2,
            'mqtt_tls' => true,
        ]));

        app(HotelConfigurationManager::class)->apply($hotel);

        $this->assertSame('broker.env.test', config('mqtt-client.connections.default.host'));
        $this->assertSame(1883, config('mqtt-client.connections.default.port'));
        $this->assertSame('env-client', config('mqtt-client.connections.default.client_id'));
        $this->assertSame('env-user', config('mqtt-client.connections.default.connection_settings.auth.username'));
        $this->assertSame('env-secret', config('mqtt-client.connections.default.connection_settings.auth.password'));
        $this->assertSame(1, config('mqtt-client.connections.default.qos'));
        $this->assertFalse(config('mqtt-client.connections.default.connection_settings.tls.enabled'));
    }
}
