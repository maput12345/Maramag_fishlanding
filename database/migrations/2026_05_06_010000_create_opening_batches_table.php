<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('OpeningBatch', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opened_by_employee_id')->constrained('Employee')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->date('bidding_date');
            $table->time('bidding_time');
            $table->string('bidding_location');
            $table->timestamps();
        });

        Schema::table('ApplicationOpening', function (Blueprint $table) {
            $table->foreignId('opening_batch_id')
                ->nullable()
                ->after('id')
                ->constrained('OpeningBatch')
                ->nullOnDelete();
        });

        Schema::table('BrokerApplication', function (Blueprint $table) {
            $table->foreignId('opening_batch_id')
                ->nullable()
                ->after('application_opening_id')
                ->constrained('OpeningBatch')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('BrokerApplication', function (Blueprint $table) {
            $table->dropConstrainedForeignId('opening_batch_id');
        });

        Schema::table('ApplicationOpening', function (Blueprint $table) {
            $table->dropConstrainedForeignId('opening_batch_id');
        });

        Schema::dropIfExists('OpeningBatch');
    }
};
