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
        Schema::create('sales_scan_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('broker_id')->constrained('Broker')->cascadeOnDelete();
            $table->string('token', 80)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['broker_id', 'expires_at']);
        });

        Schema::create('sales_scan_session_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_scan_session_id')
                ->constrained('sales_scan_sessions')
                ->cascadeOnDelete();
            $table->foreignId('fish_box_id')->nullable()->constrained('FishBox')->nullOnDelete();
            $table->string('qr_code', 255);
            $table->string('status', 30)->default('accepted');
            $table->string('message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['sales_scan_session_id', 'consumed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_scan_session_items');
        Schema::dropIfExists('sales_scan_sessions');
    }
};
