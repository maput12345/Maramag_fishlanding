<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_openings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stall_id')->constrained('stalls')->cascadeOnDelete();
            $table->foreignId('opened_by_employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('opening_status')->default('Open');
            $table->timestamps();

            $table->index(['stall_id', 'opening_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_openings');
    }
};
