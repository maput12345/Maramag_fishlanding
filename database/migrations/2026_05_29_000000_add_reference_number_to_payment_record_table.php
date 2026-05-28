<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('PaymentRecord') || Schema::hasColumn('PaymentRecord', 'reference_number')) {
            return;
        }

        Schema::table('PaymentRecord', function (Blueprint $table) {
            $table->string('reference_number', 100)->nullable()->after('payment_method');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('PaymentRecord') || !Schema::hasColumn('PaymentRecord', 'reference_number')) {
            return;
        }

        Schema::table('PaymentRecord', function (Blueprint $table) {
            $table->dropColumn('reference_number');
        });
    }
};
