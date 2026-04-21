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
        Schema::create('fish_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('broker_fish_type_id')->constrained('broker_fish_type')->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->date('price_date');
            $table->timestamps();

            $table->index(['broker_fish_type_id', 'price_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fish_prices');
    }
};
