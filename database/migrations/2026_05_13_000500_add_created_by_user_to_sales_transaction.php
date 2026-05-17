<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('SalesTransaction', function (Blueprint $table) {
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->after('broker_id')
                ->constrained('User')
                ->nullOnDelete();

            $table->index(['broker_id', 'created_by_user_id'], 'sales_broker_creator_idx');
        });
    }

    public function down(): void
    {
        Schema::table('SalesTransaction', function (Blueprint $table) {
            $table->dropIndex('sales_broker_creator_idx');
            $table->dropConstrainedForeignId('created_by_user_id');
        });
    }
};
