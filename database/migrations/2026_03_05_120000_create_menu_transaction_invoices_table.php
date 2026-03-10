<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_transaction_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('menu_transaction_id');
            $table->string('invoice_number', 50)->unique();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('deleted_at')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->index('menu_transaction_id', 'idx_menu_transaction_invoice_tx');

            $table->foreign('menu_transaction_id', 'fk_menu_transaction_invoices_transaction')
                  ->references('id')->on('menu_transactions')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_transaction_invoices');
    }
};
