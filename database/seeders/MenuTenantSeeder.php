<?php

namespace Database\Seeders;

use App\Models\MenuTenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MenuTenantSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'name' => 'Main Pantry',
                'slug' => 'main-pantry',
                'description' => 'Default pantry for shopping items.',
                'location' => 'Main Building',
                'service_charge' => 5000,
                'sort_order' => 1,
            ],
        ];

        foreach ($items as $item) {
            $tenant = MenuTenant::withTrashed()->firstOrNew([
                'slug' => $item['slug'],
            ]);

            if (!$tenant->exists) {
                $tenant->uuid = Str::uuid()->toString();
            }

            $tenant->name = $item['name'];
            $tenant->description = $item['description'];
            $tenant->location = $item['location'];
            $tenant->service_charge = $item['service_charge'];
            $tenant->sort_order = $item['sort_order'];
            $tenant->is_active = true;
            $tenant->deleted_at = null;
            $tenant->deleted_by = null;
            $tenant->save();

            $operatorRoleId = DB::table('roles')->where('category', 'operator')->value('id');
            if ($operatorRoleId) {
                $operatorUsers = User::query()->where('role_id', $operatorRoleId)->get();
                foreach ($operatorUsers as $user) {
                    if (!$user->menu_tenant_id) {
                        $user->menu_tenant_id = $tenant->id;
                        $user->save();
                    }
                    $user->menuTenants()->syncWithoutDetaching([$tenant->id]);
                }
            }
        }
    }
}
