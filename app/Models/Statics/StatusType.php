<?php

namespace App\Models\Statics;

class StatusType {

    const DEPLOYABLE = 'deployable';
    const PENDING = 'pending';
    const UNDEPLOYABLE = 'undeployable';
    const ARCHIVED = 'archived';

    public static function getTypes() {
        $types = [
            [
                'name' => 'Deployable',
                'slug' => 'deployable',
                'name_html' => '<i class="fa fa-clock text-warning"></i>&nbsp;&nbsp; Deployable'
            ],
            [
                'name' => 'Pending',
                'slug' => 'pending',
                'name_html' => '<i class="fa fa-check text-danger"></i>&nbsp;&nbsp; Pending'
            ],
            [
                'name' => 'Undeployable',
                'slug' => 'undeployable',
                'name_html' => '<i class="fa fa-times text-danger"></i>&nbsp;&nbsp; Undeployable'
            ],
            [
                'name' => 'Archived',
                'slug' => 'archived',
                'name_html' => '<i class="fa fa-archive text-info"></i>&nbsp;&nbsp; Archived'
            ]
        ];

        return collect($types);
    }
}
