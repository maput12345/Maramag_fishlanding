<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('BrokerApplication', function (Blueprint $table) {
            $table->timestamp('revision_resubmitted_at')->nullable()->after('submitted_at');
            $table->unsignedInteger('revision_count')->default(0)->after('revision_resubmitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('BrokerApplication', function (Blueprint $table) {
            $table->dropColumn([
                'revision_resubmitted_at',
                'revision_count',
            ]);
        });
    }
};
