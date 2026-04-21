<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fish_boxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('broker_id')->constrained('brokers')->cascadeOnDelete();
            $table->uuid('qr_code')->unique();
            $table->enum('box_status', ['In Stock', 'Sold', 'Returned', 'Missing'])->default('In Stock');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fish_boxes');
    }
};
