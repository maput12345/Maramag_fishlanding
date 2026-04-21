<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('broker_applications')->cascadeOnDelete();
            $table->foreignId('requirement_type_id')->constrained('requirement_types')->cascadeOnDelete();
            $table->foreignId('verified_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('file_path');
            $table->string('document_number')->nullable();
            $table->string('issuing_office')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('verification_status')->default('Pending');
            $table->timestamp('verification_date')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();

            $table->unique(['application_id', 'requirement_type_id'], 'app_requirements_application_type_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_requirements');
    }
};
