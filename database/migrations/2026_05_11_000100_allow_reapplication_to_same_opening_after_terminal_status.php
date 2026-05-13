<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('BrokerApplication', function (Blueprint $table) {
            $table->index(['user_id', 'application_opening_id'], 'broker_apps_user_opening_idx');
            $table->dropUnique('broker_apps_user_opening_uq');
        });
    }

    public function down(): void
    {
        Schema::table('BrokerApplication', function (Blueprint $table) {
            $table->unique(['user_id', 'application_opening_id'], 'broker_apps_user_opening_uq');
            $table->dropIndex('broker_apps_user_opening_idx');
        });
    }
};
