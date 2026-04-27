<?php

use App\Models\RequirementType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requirement_types', function (Blueprint $table) {
            $table->string('audience')->default(RequirementType::APPLICANT_TYPE_BOTH)->after('description');
            $table->unsignedInteger('sort_order')->default(0)->after('audience');
        });
    }

    public function down(): void
    {
        Schema::table('requirement_types', function (Blueprint $table) {
            $table->dropColumn(['audience', 'sort_order']);
        });
    }
};
