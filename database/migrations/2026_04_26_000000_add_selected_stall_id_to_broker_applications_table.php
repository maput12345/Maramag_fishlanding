<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broker_applications', function (Blueprint $table) {
            $table->foreignId('selected_stall_id')
                ->nullable()
                ->after('application_opening_id')
                ->constrained('stalls')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('broker_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('selected_stall_id');
        });
    }
};
