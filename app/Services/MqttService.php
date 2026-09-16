<?php

namespace App\Services;

use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

class MqttService
{
    public function publish(string $topic, string $message, bool $retain = true): void
    {
        $mqtt = $this->client();
        $mqtt->connect($this->connectionSettings(), true);
        $mqtt->publish($topic, $message, (int) config('mqtt-client.connections.default.qos', 1), $retain);
        $mqtt->disconnect();
    }

    /**
     * Keep this method inside a long-running command/worker because the MQTT
     * loop waits for messages until the process is interrupted.
     */
    public function subscribe(string $topic, callable $handler): void
    {
        $mqtt = $this->client();
        $mqtt->connect($this->connectionSettings(), true);
        $mqtt->subscribe($topic, $handler, (int) config('mqtt-client.connections.default.qos', 1));

        try {
            $mqtt->loop(true);
        } finally {
            $mqtt->disconnect();
        }
    }

    private function client(): MqttClient
    {
        return new MqttClient(
            (string) config('mqtt-client.connections.default.host'),
            (int) config('mqtt-client.connections.default.port', 1883),
            config('mqtt-client.connections.default.client_id') ?: uniqid('hotel-app-', true),
            (string) config('mqtt-client.connections.default.protocol', MqttClient::MQTT_3_1)
        );
    }

    private function connectionSettings(): ConnectionSettings
    {
        $tls = config('mqtt-client.connections.default.connection_settings.tls', []);

        return (new ConnectionSettings)
            ->setUsername(config('mqtt-client.connections.default.connection_settings.auth.username'))
            ->setPassword(config('mqtt-client.connections.default.connection_settings.auth.password'))
            ->setUseTls((bool) ($tls['enabled'] ?? false))
            ->setTlsSelfSignedAllowed((bool) ($tls['allow_self_signed_certificate'] ?? false))
            ->setTlsVerifyPeer((bool) ($tls['verify_peer'] ?? true))
            ->setTlsVerifyPeerName((bool) ($tls['verify_peer_name'] ?? true));
    }
}
