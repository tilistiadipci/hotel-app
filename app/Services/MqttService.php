<?php

namespace App\Services;

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

class MqttService
{
    public function publish($topic, $message, bool $retain = true)
    {
        $server   = config('mqtt-client.connections.default.host');
        $port     = config('mqtt-client.connections.default.port');
        $clientId = config('mqtt-client.connections.default.client_id') ?? uniqid();

        $connectionSettings = (new ConnectionSettings)
            ->setUsername(config('mqtt-client.connections.default.connection_settings.auth.username'))
            ->setPassword(config('mqtt-client.connections.default.connection_settings.auth.password'));

        $mqtt = new MqttClient($server, $port, $clientId);

        $mqtt->connect($connectionSettings, true);

        $mqtt->publish(
            $topic,
            $message,
            config('mqtt-client.connections.default.qos', 1),
            $retain
        );

        $mqtt->disconnect();
    }
}
