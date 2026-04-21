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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->date('sales_date');
            $table->foreignId('broker_id')->constrained('brokers')->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('buyers')->cascadeOnDelete();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->enum('status', ['Deleted', 'Active', 'Partially_Paid', 'Paid'])->default('Active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
