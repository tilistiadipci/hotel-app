<?php

namespace App\Models\Statics;

class Readers {

    public static function getModels() {
        $models = [
            [
                'id' => 'KG02',
                'name' => 'KG02',
            ],
        ];

        return collect($models);
    }

    public static function getTypes() {
        $types = [
            [
                'id' => 'RFID',
                'name' => 'RFID',
            ],
            [
                'id' => 'BLE',
                'name' => 'BLE',
            ],
            [
                'id' => 'UHF',
                'name' => 'UHF',
            ],
        ];

        return collect($types);
    }

    public static function getProtocols() {
        $protocols = [
            [
                'id' => 'http',
                'name' => 'HTTP',
            ],
            [
                'id' => 'mqtt',
                'name' => 'MQTT',
            ],
        ];

        return collect($protocols);
    }
}
