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
        Schema::create('deliveo_sync_logs', function (Blueprint $table) {
            $table->id();

            $table->string('deliveo_id')->nullable()->index();
            $table->string('phone_number')->index();
            $table->string('deliveo_status'); // The raw status from Deliveo
            $table->dateTime('delivery_date')->nullable();
            $table->string('order_amount')->nullable();
            $table->string('customer_name')->nullable();

            // Logic for Salesrender
            $table->string('salesrender_order_id')->nullable()->index();

            $table->string('sync_status')->default('pending')->index();

            // To store errors or why a match failed (e.g., "Found 3 orders for this phone")
            $table->text('error_message')->nullable();
            $table->json('api_response')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveo_sync_logs');
    }
};
