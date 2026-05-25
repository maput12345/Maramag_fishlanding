<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('FishBox') && ! $this->indexExists('FishBox', 'fish_box_broker_id_id_idx')) {
            Schema::table('FishBox', function (Blueprint $table) {
                $table->index(['broker_id', 'id'], 'fish_box_broker_id_id_idx');
            });
        }

        if (Schema::hasTable('FishBoxStockCycle') && ! $this->indexExists('FishBoxStockCycle', 'fish_box_stock_cycle_box_id_id_idx')) {
            Schema::table('FishBoxStockCycle', function (Blueprint $table) {
                $table->index(['fish_box_id', 'id'], 'fish_box_stock_cycle_box_id_id_idx');
            });
        }

        if (Schema::hasTable('TransactionLineItem') && ! $this->indexExists('TransactionLineItem', 'transaction_line_purchase_id_id_idx')) {
            Schema::table('TransactionLineItem', function (Blueprint $table) {
                $table->index(['fish_box_purchase_id', 'id'], 'transaction_line_purchase_id_id_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('TransactionLineItem') && $this->indexExists('TransactionLineItem', 'transaction_line_purchase_id_id_idx')) {
            Schema::table('TransactionLineItem', function (Blueprint $table) {
                $table->dropIndex('transaction_line_purchase_id_id_idx');
            });
        }

        if (Schema::hasTable('FishBoxStockCycle') && $this->indexExists('FishBoxStockCycle', 'fish_box_stock_cycle_box_id_id_idx')) {
            Schema::table('FishBoxStockCycle', function (Blueprint $table) {
                $table->dropIndex('fish_box_stock_cycle_box_id_id_idx');
            });
        }

        if (Schema::hasTable('FishBox') && $this->indexExists('FishBox', 'fish_box_broker_id_id_idx')) {
            Schema::table('FishBox', function (Blueprint $table) {
                $table->dropIndex('fish_box_broker_id_id_idx');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('{$table}')"))
                ->contains(fn (object $index): bool => ($index->name ?? null) === $indexName);
        }

        $database = DB::getDatabaseName();

        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $indexName)
            ->exists();
    }
};
