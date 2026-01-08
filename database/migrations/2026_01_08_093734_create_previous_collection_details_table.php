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
        Schema::create('previous_collection_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prv_collection_id');
            $table->string('pc_number');
            $table->integer('status');
            $table->unsignedBigInteger('changed_by');
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('previous_collection_details');
    }
};
