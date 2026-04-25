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
        Schema::table('sales', function (Blueprint $table) {
            $table->index(['broker_id', 'sales_date', 'status'], 'sales_broker_date_status_idx');
            $table->index(['sales_date', 'status'], 'sales_date_status_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['sale_id', 'payment_date'], 'payments_sale_date_idx');
            $table->index(['payment_date', 'payment_method'], 'payments_date_method_idx');
        });

        Schema::table('sales_details', function (Blueprint $table) {
            $table->index(['sale_id', 'fish_box_purchase_id'], 'sales_details_sale_purchase_idx');
        });

        Schema::table('fish_boxes', function (Blueprint $table) {
            $table->index(['broker_id', 'box_status', 'deleted_at'], 'fish_boxes_broker_status_deleted_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fish_boxes', function (Blueprint $table) {
            $table->dropIndex('fish_boxes_broker_status_deleted_idx');
        });

        Schema::table('sales_details', function (Blueprint $table) {
            $table->dropIndex('sales_details_sale_purchase_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_sale_date_idx');
            $table->dropIndex('payments_date_method_idx');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_broker_date_status_idx');
            $table->dropIndex('sales_date_status_idx');
        });
    }
};
