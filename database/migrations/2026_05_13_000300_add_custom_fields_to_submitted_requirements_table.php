<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('SubmittedRequirement', function (Blueprint $table) {
            $table->foreignId('requirement_type_id')->nullable()->change();
            $table->string('custom_title')->nullable()->after('requirement_type_id');
            $table->text('custom_description')->nullable()->after('custom_title');
            $table->boolean('is_additional')->default(false)->after('custom_description');
        });
    }

    public function down(): void
    {
        Schema::table('SubmittedRequirement', function (Blueprint $table) {
            $table->dropColumn(['custom_title', 'custom_description', 'is_additional']);
            $table->foreignId('requirement_type_id')->nullable(false)->change();
        });
    }
};
