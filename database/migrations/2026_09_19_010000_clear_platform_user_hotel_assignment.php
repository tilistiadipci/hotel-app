<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereIn('role_id', function ($query) {
                $query->select('id')->from('roles')
                    ->whereIn('category', ['master', 'superadmin', 'manager']);
            })
            ->update(['hotel_id' => null]);
    }

    public function down(): void
    {
        // Manager/platform users intentionally do not own one primary hotel.
    }
};
