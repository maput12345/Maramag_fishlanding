<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var string[]
     */
    private array $scheduleColumns = [
        'start_date',
        'end_date',
        'bidding_date',
        'bidding_time',
        'bidding_location',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('ApplicationOpening') || !Schema::hasTable('OpeningBatch')) {
            return;
        }

        $hasScheduleColumns = collect($this->scheduleColumns)
            ->every(fn (string $column): bool => Schema::hasColumn('ApplicationOpening', $column));

        if ($hasScheduleColumns && Schema::hasColumn('ApplicationOpening', 'opening_batch_id')) {
            DB::table('ApplicationOpening')
                ->whereNull('opening_batch_id')
                ->orderBy('id')
                ->get()
                ->each(function ($opening): void {
                    $batchId = DB::table('OpeningBatch')->insertGetId([
                        'opened_by_employee_id' => $opening->opened_by_employee_id,
                        'start_date' => $opening->start_date,
                        'end_date' => $opening->end_date,
                        'bidding_date' => $opening->bidding_date ?: $opening->start_date,
                        'bidding_time' => $opening->bidding_time ?: '09:00:00',
                        'bidding_location' => $opening->bidding_location ?: 'Maramag Fish Landing',
                        'created_at' => $opening->created_at ?? now(),
                        'updated_at' => $opening->updated_at ?? now(),
                    ]);

                    DB::table('ApplicationOpening')
                        ->where('id', $opening->id)
                        ->update(['opening_batch_id' => $batchId]);
                });
        }

        Schema::table('ApplicationOpening', function (Blueprint $table) {
            foreach ($this->scheduleColumns as $column) {
                if (Schema::hasColumn('ApplicationOpening', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('ApplicationOpening', function (Blueprint $table) {
            if (!Schema::hasColumn('ApplicationOpening', 'start_date')) {
                $table->date('start_date')->nullable()->after('opened_by_employee_id');
            }

            if (!Schema::hasColumn('ApplicationOpening', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }

            if (!Schema::hasColumn('ApplicationOpening', 'bidding_date')) {
                $table->date('bidding_date')->nullable()->after('end_date');
            }

            if (!Schema::hasColumn('ApplicationOpening', 'bidding_time')) {
                $table->time('bidding_time')->nullable()->after('bidding_date');
            }

            if (!Schema::hasColumn('ApplicationOpening', 'bidding_location')) {
                $table->string('bidding_location')->nullable()->after('bidding_time');
            }
        });

        if (!Schema::hasTable('OpeningBatch') || !Schema::hasColumn('ApplicationOpening', 'opening_batch_id')) {
            return;
        }

        DB::table('ApplicationOpening')
            ->join('OpeningBatch', 'OpeningBatch.id', '=', 'ApplicationOpening.opening_batch_id')
            ->update([
                'ApplicationOpening.start_date' => DB::raw('OpeningBatch.start_date'),
                'ApplicationOpening.end_date' => DB::raw('OpeningBatch.end_date'),
                'ApplicationOpening.bidding_date' => DB::raw('OpeningBatch.bidding_date'),
                'ApplicationOpening.bidding_time' => DB::raw('OpeningBatch.bidding_time'),
                'ApplicationOpening.bidding_location' => DB::raw('OpeningBatch.bidding_location'),
            ]);
    }
};
