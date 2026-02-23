<?php

namespace App\Models\Statics;

class CategoryType {
    public static function getTypes() {
        $types = [
            [
                'name' => trans('common.asset.title'),
                'menu' => 'Assets',
                'slug' => 'asset',
                'icon' => trans('common.icons.assets'),
                'url' => route('assets.index'),
            ],
            [
                'name' => trans('common.accessory.title'),
                'menu' => 'Accessories',
                'slug' => 'accessory',
                'icon' => trans('common.icons.accessories'),
                'url' => route('accessories.index'),
            ],
            [
                'name' => trans('common.consumable.title'),
                'menu' => 'Consumables',
                'slug' => 'consumable',
                'icon' => trans('common.icons.consumables'),
                'url' => route('consumables.index'),
            ],
            [
                'name' => trans('common.component.title'),
                'menu' => 'Components',
                'slug' => 'component',
                'icon' => trans('common.icons.components'),
                'url' => route('components.index'),
            ],
            [
                'name' => trans('common.license.title'),
                'menu' => 'Licenses',
                'slug' => 'license',
                'icon' => trans('common.icons.licenses'),
                'url' => route('licenses.index'),
            ]
        ];

        return collect($types);
    }
}
