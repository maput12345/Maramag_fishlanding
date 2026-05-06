<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('BrokerApplication', function (Blueprint $table) {
            $table->string('applicant_type')->nullable()->after('application_opening_id');
            $table->string('civil_status')->nullable()->after('suffix');
            $table->string('spouse_name')->nullable()->after('civil_status');
            $table->string('spouse_contact_number')->nullable()->after('spouse_name');
            $table->text('business_address')->nullable()->after('business_name');
            $table->string('representative_name')->nullable()->after('business_address');
            $table->string('representative_position')->nullable()->after('representative_name');
        });
    }

    public function down(): void
    {
        Schema::table('BrokerApplication', function (Blueprint $table) {
            $table->dropColumn([
                'applicant_type',
                'civil_status',
                'spouse_name',
                'spouse_contact_number',
                'business_address',
                'representative_name',
                'representative_position',
            ]);
        });
    }
};
