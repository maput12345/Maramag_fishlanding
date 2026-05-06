<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('User', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('status');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('last_name')->nullable()->after('middle_name');
            $table->string('suffix', 50)->nullable()->after('last_name');
            $table->string('contact_number', 50)->nullable()->after('suffix');
            $table->text('address')->nullable()->after('contact_number');
        });
    }

    public function down(): void
    {
        Schema::table('User', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'middle_name',
                'last_name',
                'suffix',
                'contact_number',
                'address',
            ]);
        });
    }
};
