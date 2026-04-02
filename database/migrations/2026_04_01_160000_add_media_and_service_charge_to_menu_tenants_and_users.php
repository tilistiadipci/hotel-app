<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_tenants', function (Blueprint $table) {
            $table->foreignId('image_id')->nullable()->after('description')->constrained('medias')->nullOnDelete();
            $table->decimal('service_charge', 8, 2)->default(0)->after('location');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('menu_tenant_id')->nullable()->after('role_id')->constrained('menu_tenants')->nullOnDelete();
            $table->index('menu_tenant_id', 'idx_users_menu_tenant');
        });

        Schema::create('menu_tenant_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_tenant_id')->constrained('menu_tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['menu_tenant_id', 'user_id'], 'uq_menu_tenant_user');
        });

        $operatorRoleId = DB::table('roles')->where('category', 'operator')->value('id');
        $defaultTenantId = DB::table('menu_tenants')->orderBy('id')->value('id');

        if ($operatorRoleId && $defaultTenantId) {
            DB::table('users')
                ->where('role_id', $operatorRoleId)
                ->whereNull('menu_tenant_id')
                ->update(['menu_tenant_id' => $defaultTenantId]);

            $operatorUsers = DB::table('users')
                ->where('role_id', $operatorRoleId)
                ->whereNotNull('menu_tenant_id')
                ->get(['id', 'menu_tenant_id']);

            foreach ($operatorUsers as $user) {
                DB::table('menu_tenant_user')->updateOrInsert(
                    [
                        'menu_tenant_id' => $user->menu_tenant_id,
                        'user_id' => $user->id,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_tenant_user');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_menu_tenant');
            $table->dropConstrainedForeignId('menu_tenant_id');
        });

        Schema::table('menu_tenants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('image_id');
            $table->dropColumn('service_charge');
        });
    }
};
