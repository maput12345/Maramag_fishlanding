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
        Schema::create('broker_fish_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('broker_id')->constrained('brokers')->cascadeOnDelete();
            $table->foreignId('fish_type_id')->constrained('fish_types')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['broker_id', 'fish_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('broker_fish_type');
    }
};
