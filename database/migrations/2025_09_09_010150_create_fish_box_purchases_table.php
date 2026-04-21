<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fish_box_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fish_box_id')->constrained('fish_boxes')->cascadeOnDelete();
            $table->foreignId('fish_type_id')->constrained('fish_types')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('purchase_date');
            $table->decimal('cost_price', 10, 2);
            $table->timestamps();

            $table->index(['fish_box_id', 'purchase_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fish_box_purchases');
    }
};
