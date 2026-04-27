<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broker_application_review_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('broker_application_id')->constrained('broker_applications')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->json('draft_payload');
            $table->timestamp('last_saved_at')->nullable();
            $table->timestamps();

            $table->unique(['broker_application_id', 'employee_id'], 'broker_app_review_drafts_app_employee_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broker_application_review_drafts');
    }
};
