<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_configurations', function (Blueprint $table) {
            $table->id();
            $table->uuid('hotel_id')->unique();
            $table->string('media_disk')->default('media');
            $table->text('media_root');
            $table->string('mqtt_host')->nullable();
            $table->unsignedSmallInteger('mqtt_port')->default(1883);
            $table->string('mqtt_client_id')->nullable();
            $table->text('mqtt_username')->nullable();
            $table->text('mqtt_password')->nullable();
            $table->unsignedTinyInteger('mqtt_qos')->default(1);
            $table->boolean('mqtt_tls')->default(false);
            $table->json('additional_settings')->nullable();
            $table->timestamps();

            $table->foreign('hotel_id')->references('id')->on('hotels')->cascadeOnDelete();
        });

        Schema::create('hotel_visit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('hotel_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->ipAddress('ip_address')->index();
            $table->string('method', 10);
            $table->text('url');
            $table->string('route_name')->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->timestamp('visited_at')->index();
            $table->timestamps();

            $table->foreign('hotel_id')->references('id')->on('hotels')->nullOnDelete();
            $table->index(['hotel_id', 'visited_at']);
        });

        $fallbackRoot = (string) config('filesystems.disks.media.root', storage_path('app/hotels'));

        DB::table('hotels')->orderBy('created_at')->get(['id', 'code'])->each(function ($hotel, $index) use ($fallbackRoot) {
            DB::table('hotel_configurations')->insert([
                'hotel_id' => $hotel->id,
                'media_disk' => 'media',
                // Keep the first (legacy) hotel's existing media location.
                // Any additional pre-existing hotel gets an isolated directory.
                'media_root' => $index === 0
                    ? $fallbackRoot
                    : rtrim($fallbackRoot, '/\\').DIRECTORY_SEPARATOR.$hotel->id,
                'mqtt_host' => config('mqtt-client.connections.default.host'),
                'mqtt_port' => config('mqtt-client.connections.default.port', 1883),
                'mqtt_client_id' => config('mqtt-client.connections.default.client_id'),
                'mqtt_username' => ($username = config('mqtt-client.connections.default.connection_settings.auth.username')) ? Crypt::encryptString($username) : null,
                'mqtt_password' => ($password = config('mqtt-client.connections.default.connection_settings.auth.password')) ? Crypt::encryptString($password) : null,
                'mqtt_qos' => config('mqtt-client.connections.default.qos', 1),
                'mqtt_tls' => config('mqtt-client.connections.default.connection_settings.tls.enabled', false),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_visit_logs');
        Schema::dropIfExists('hotel_configurations');
    }
};
