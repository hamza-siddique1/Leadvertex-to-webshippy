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
        Schema::create('deliveo_orders', function (Blueprint $table) {
            $table->id();

            $table->string('deliveo_id')->unique();
            $table->string('order_id')->nullable();

            $table->timestamp('last_modified')->nullable();
            $table->timestamp('invoice_created_at')->nullable();
            $table->string('invoice_path')->nullable();

            $table->json('payload');

            $table->timestamps();

            $table->index(['deliveo_id', 'invoice_created_at', 'last_modified']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveo_orders');
    }
};
