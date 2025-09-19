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
        Schema::create('customer_orders', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('customer_group');
            $table->string('customer_branch');
            $table->string('customer_po_no')->nullable();
            $table->decimal('po_amount', 12, 2);
            $table->string('orn_number')->unique();
            $table->date('order_request_date');
            $table->text('remarks')->nullable();
            $table->string('sales_branch')->nullable();
            $table->string('payment_type')->nullable();
            $table->date('approval_date')->nullable();
            $table->text('approval_remark')->nullable();
            $table->string('sales_order_no')->nullable();
            $table->date('sales_order_date')->nullable();
            $table->string('quotation_no')->nullable();
            $table->date('quotation_date')->nullable();
            $table->string('payment_receipt', 255)->nullable();
            $table->integer('status')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_orders');
    }
};