<?php

namespace App\Models\Statics;

class AssetMaintenanceType {

    const REPAIR = 'repair';
    const UPGRADE = 'upgrade';
    const MAINTENANCE = 'maintenance';

    public static function getTypes() {
        $types = [
            [
                'id' => 'repair',
                'name' => 'Repair',
                'slug' => 'repair',
            ],
            [
                'id' => 'upgrade',
                'name' => 'Upgrade',
                'slug' => 'upgrade',
            ],
            [
                'id' => 'maintenance',
                'name' => 'Maintenance',
                'slug' => 'maintenance',
            ]
        ];

        return collect($types);
    }
}
