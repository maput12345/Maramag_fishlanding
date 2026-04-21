<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broker_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('application_opening_id')->constrained('application_openings')->cascadeOnDelete();
            $table->foreignId('reviewed_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('selected_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();
            $table->string('business_name')->nullable();
            $table->text('address');
            $table->string('contact_number');
            $table->string('application_status')->default('Submitted');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('review_date')->nullable();
            $table->timestamp('selected_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'application_opening_id'], 'broker_apps_user_opening_uq');
            $table->index(['application_opening_id', 'application_status'], 'broker_apps_opening_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broker_applications');
    }
};
