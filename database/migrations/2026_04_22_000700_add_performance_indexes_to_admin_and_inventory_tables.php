<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fish_inventory', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'fish_inventory_status_created_idx');
            $table->index(['fish_box_purchase_id', 'created_at'], 'fish_inventory_purchase_created_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('status', 'users_status_idx');
        });

        Schema::table('broker_applications', function (Blueprint $table) {
            $table->index(['application_status', 'submitted_at'], 'broker_apps_status_submitted_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('broker_applications', function (Blueprint $table) {
            $table->dropIndex('broker_apps_status_submitted_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_status_idx');
        });

        Schema::table('fish_inventory', function (Blueprint $table) {
            $table->dropIndex('fish_inventory_status_created_idx');
            $table->dropIndex('fish_inventory_purchase_created_idx');
        });
    }
};
