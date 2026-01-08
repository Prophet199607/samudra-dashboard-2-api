<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('previous_collections', function (Blueprint $table) {
            $table->id();
            $table->string('pc_number')->unique();
            $table->string('customer_code');
            $table->string('customer_name');
            $table->string('payment_receipt', 255)->nullable();
            $table->text('payment_remark')->nullable();
            $table->boolean('payment_confirmed')->default(0);
            $table->string('receipt_no')->nullable();
            $table->decimal('receipt_amount', 12, 2)->nullable();
            $table->string('cash_in_no')->nullable();
            $table->decimal('cash_in_amount', 12, 2)->nullable();
            $table->text('cash_in_remark')->nullable();
            $table->integer('status')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('previous_collections');
    }
};
