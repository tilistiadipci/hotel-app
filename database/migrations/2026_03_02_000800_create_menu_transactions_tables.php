<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('player_id');
            $table->string('guest_name', 150)->nullable();

            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('service_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);

            $table->enum('payment_method', ['qris', 'bill']);
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'cancelled'])->default('pending');

            $table->enum('status', ['ordered', 'processing', 'completed', 'cancelled'])->default('ordered');

            $table->dateTime('paid_at')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('deleted_at')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->index('player_id', 'idx_player_id');
            $table->index('status', 'idx_status');
            $table->index('payment_status', 'idx_payment_status');

            $table->foreign('player_id', 'fk_menu_transactions_players')
                  ->references('id')->on('players')
                  ->restrictOnDelete()
                  ->cascadeOnUpdate();
        });


        Schema::create('menu_transaction_details', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('menu_transaction_id');
            $table->unsignedBigInteger('menu_id');

            $table->string('menu_name', 150);
            $table->decimal('price', 15, 2);
            $table->integer('quantity')->default(1);
            $table->decimal('subtotal', 15, 2);

            $table->text('notes')->nullable();

            $table->enum('status', ['ordered', 'prepared', 'delivered', 'cancelled'])->default('ordered');

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('deleted_at')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->index('menu_transaction_id', 'idx_transaction');
            $table->index('menu_id', 'idx_menu');
            $table->index('status', 'idx_detail_status');

            $table->foreign('menu_transaction_id', 'fk_menu_transaction_details_transaction')
                  ->references('id')->on('menu_transactions')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_transaction_details');
        Schema::dropIfExists('menu_transactions');
    }
};
