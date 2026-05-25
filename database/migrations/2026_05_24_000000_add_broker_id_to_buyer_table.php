<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('Buyer', 'broker_id')) {
            Schema::table('Buyer', function (Blueprint $table): void {
                $table->foreignId('broker_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('Broker')
                    ->nullOnDelete();

                $table->index(['broker_id', 'first_name', 'last_name', 'contact']);
            });
        }

        DB::table('Buyer')
            ->joinSub(
                DB::table('SalesTransaction')
                    ->select('buyer_id')
                    ->selectRaw('MIN(broker_id) as broker_id')
                    ->groupBy('buyer_id')
                    ->havingRaw('COUNT(DISTINCT broker_id) = 1'),
                'single_broker_sales',
                'Buyer.id',
                '=',
                'single_broker_sales.buyer_id'
            )
            ->whereNull('Buyer.broker_id')
            ->update(['Buyer.broker_id' => DB::raw('single_broker_sales.broker_id')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('Buyer', 'broker_id')) {
            Schema::table('Buyer', function (Blueprint $table): void {
                $table->dropForeign(['broker_id']);
                $table->dropIndex(['broker_id', 'first_name', 'last_name', 'contact']);
                $table->dropColumn('broker_id');
            });
        }
    }
};
