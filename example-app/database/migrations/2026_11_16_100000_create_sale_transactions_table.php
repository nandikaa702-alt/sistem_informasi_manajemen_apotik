<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 50)->unique();
            $table->decimal('subtotal', 14, 2);
            $table->decimal('paid_amount', 14, 2);
            $table->decimal('change_amount', 14, 2);
            $table->timestamp('sold_at')->index();
            $table->timestamps();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('sale_transaction_id')
                ->nullable()
                ->after('id')
                ->constrained('sale_transactions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sale_transaction_id');
        });

        Schema::dropIfExists('sale_transactions');
    }
};