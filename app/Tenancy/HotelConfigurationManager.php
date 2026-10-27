<?php

namespace App\Tenancy;

use App\Models\Hotel;

class HotelConfigurationManager
{
    public function apply(Hotel $hotel): void
    {
        $settings = $hotel->configuration;

        if (! $settings) {
            return;
        }

        $environment = config('mqtt-client.environment_defaults');
        $mqttConfiguration = $settings->use_custom_mqtt
            ? [
                'mqtt-client.connections.default.host' => $settings->mqtt_host,
                'mqtt-client.connections.default.port' => $settings->mqtt_port,
                'mqtt-client.connections.default.client_id' => $settings->mqtt_client_id,
                'mqtt-client.connections.default.qos' => $settings->mqtt_qos,
                'mqtt-client.connections.default.connection_settings.auth.username' => $settings->mqtt_username,
                'mqtt-client.connections.default.connection_settings.auth.password' => $settings->mqtt_password,
                'mqtt-client.connections.default.connection_settings.tls.enabled' => $settings->mqtt_tls,
            ]
            : [
                'mqtt-client.connections.default.host' => $environment['host'],
                'mqtt-client.connections.default.port' => $environment['port'],
                'mqtt-client.connections.default.client_id' => $environment['client_id'],
                'mqtt-client.connections.default.qos' => $environment['qos'],
                'mqtt-client.connections.default.connection_settings.auth.username' => $environment['username'],
                'mqtt-client.connections.default.connection_settings.auth.password' => $environment['password'],
                'mqtt-client.connections.default.connection_settings.tls.enabled' => $environment['tls'],
            ];

        // Media files live in one shared root. Hotel configuration only
        // controls MQTT; it must not alter the media disk per request.
        config($mqttConfiguration);
    }
}
