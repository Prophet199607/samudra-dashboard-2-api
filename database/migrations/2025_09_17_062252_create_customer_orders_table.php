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
            $table->text('remark')->nullable();
            $table->string('sales_branch')->nullable();
            $table->string('sales_branch_code')->nullable();
            $table->string('payment_type')->nullable();
            $table->date('approval_date')->nullable();
            $table->text('approval_remark')->nullable();
            $table->string('sales_order_no')->nullable();
            $table->decimal('sales_order_amount', 12, 2)->nullable();
            $table->date('sales_order_date')->nullable();
            $table->string('quotation_no')->nullable();
            $table->decimal('quotation_amount', 12, 2)->nullable();
            $table->date('quotation_date')->nullable();
            $table->string('payment_receipt', 255)->nullable();
            $table->text('payment_remark')->nullable();
            $table->boolean('payment_confirmed')->default(0);
            $table->string('invoice_no')->nullable();
            $table->decimal('invoice_amount', 12, 2)->nullable();
            $table->string('cash_in_no')->nullable();
            $table->decimal('cash_in_amount', 12, 2)->nullable();
            $table->text('cash_in_remark')->nullable();
            $table->string('delivery_type')->nullable();
            $table->boolean('is_delayed')->default(0);
            $table->text('delay_reason')->nullable();
            $table->string('bus_no')->nullable();
            $table->string('way_bill_no')->nullable();
            $table->string('tracking_no')->nullable();
            $table->string('vehicle_no')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('courier_name')->nullable();
            $table->string('no_of_boxes')->nullable();
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
