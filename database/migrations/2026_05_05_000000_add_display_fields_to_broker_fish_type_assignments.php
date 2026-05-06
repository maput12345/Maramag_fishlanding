<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('BrokerFishTypeAssignment', function (Blueprint $table) {
            if (!Schema::hasColumn('BrokerFishTypeAssignment', 'display_name')) {
                $table->string('display_name')->nullable()->after('fish_type_id');
            }

            if (!Schema::hasColumn('BrokerFishTypeAssignment', 'display_description')) {
                $table->text('display_description')->nullable()->after('display_name');
            }
        });

        DB::table('BrokerFishTypeAssignment')
            ->join('FishType', 'FishType.id', '=', 'BrokerFishTypeAssignment.fish_type_id')
            ->select([
                'BrokerFishTypeAssignment.id',
                'FishType.name',
                'FishType.description',
            ])
            ->orderBy('BrokerFishTypeAssignment.id')
            ->get()
            ->each(function ($assignment): void {
                DB::table('BrokerFishTypeAssignment')
                    ->where('id', $assignment->id)
                    ->update([
                        'display_name' => $assignment->name,
                        'display_description' => $assignment->description,
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('BrokerFishTypeAssignment', function (Blueprint $table) {
            if (Schema::hasColumn('BrokerFishTypeAssignment', 'display_description')) {
                $table->dropColumn('display_description');
            }

            if (Schema::hasColumn('BrokerFishTypeAssignment', 'display_name')) {
                $table->dropColumn('display_name');
            }
        });
    }
};
