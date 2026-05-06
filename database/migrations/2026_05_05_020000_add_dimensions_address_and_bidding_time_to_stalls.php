<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Stall', function (Blueprint $table) {
            $table->decimal('length_meters', 8, 2)->nullable()->after('stall_status');
            $table->decimal('width_meters', 8, 2)->nullable()->after('length_meters');
            $table->decimal('area_sqm', 10, 2)->nullable()->after('width_meters');
            $table->string('address')->nullable()->after('area_sqm');
        });

        Schema::table('ApplicationOpening', function (Blueprint $table) {
            $table->time('bidding_time')->nullable()->after('bidding_date');
        });
    }

    public function down(): void
    {
        Schema::table('ApplicationOpening', function (Blueprint $table) {
            $table->dropColumn('bidding_time');
        });

        Schema::table('Stall', function (Blueprint $table) {
            $table->dropColumn([
                'length_meters',
                'width_meters',
                'area_sqm',
                'address',
            ]);
        });
    }
};
