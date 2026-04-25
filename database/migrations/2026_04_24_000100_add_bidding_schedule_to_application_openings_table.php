<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_openings', function (Blueprint $table) {
            $table->date('bidding_date')->nullable()->after('end_date');
            $table->string('bidding_location')->nullable()->after('bidding_date');
        });

        DB::table('application_openings')
            ->update([
                'bidding_date' => DB::raw('start_date'),
                'bidding_location' => 'Maramag Fish Landing',
            ]);
    }

    public function down(): void
    {
        Schema::table('application_openings', function (Blueprint $table) {
            $table->dropColumn(['bidding_date', 'bidding_location']);
        });
    }
};
