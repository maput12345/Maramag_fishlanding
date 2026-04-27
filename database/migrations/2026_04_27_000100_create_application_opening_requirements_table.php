<?php

use App\Models\RequirementType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_opening_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_opening_id')->constrained('application_openings')->cascadeOnDelete();
            $table->foreignId('requirement_type_id')->constrained('requirement_types')->cascadeOnDelete();
            $table->boolean('is_required')->default(true);
            $table->string('audience')->default(RequirementType::APPLICANT_TYPE_BOTH);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['application_opening_id', 'requirement_type_id'], 'opening_requirements_opening_type_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_opening_requirements');
    }
};
