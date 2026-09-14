<?php

namespace App\Tenancy;

use App\Models\Hotel;
use Illuminate\Support\Facades\Storage;

class HotelConfigurationManager
{
    public function apply(Hotel $hotel): void
    {
        $settings = $hotel->configuration;

        if (! $settings) {
            return;
        }

        config([
            'filesystems.disks.media.driver' => 'local',
            'filesystems.disks.media.root' => app(HotelMediaPath::class)->absoluteRoot($settings->media_root),
            'filesystems.disks.media.visibility' => 'public',
            'mqtt-client.connections.default.host' => $settings->mqtt_host,
            'mqtt-client.connections.default.port' => $settings->mqtt_port,
            'mqtt-client.connections.default.client_id' => $settings->mqtt_client_id,
            'mqtt-client.connections.default.qos' => $settings->mqtt_qos,
            'mqtt-client.connections.default.connection_settings.auth.username' => $settings->mqtt_username,
            'mqtt-client.connections.default.connection_settings.auth.password' => $settings->mqtt_password,
            'mqtt-client.connections.default.connection_settings.tls.enabled' => $settings->mqtt_tls,
        ]);

        // Laravel caches resolved filesystem instances. Forget the previous
        // instance so long-running workers cannot reuse another hotel's root.
        Storage::forgetDisk('media');
    }
}
