<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'suffix')) {
                $table->string('suffix')->nullable()->after('last_name');
            }
        });

        Schema::table('brokers', function (Blueprint $table) {
            if (!Schema::hasColumn('brokers', 'application_id')) {
                $table->foreignId('application_id')->nullable()->after('user_id')->constrained('broker_applications')->nullOnDelete();
                $table->unique('application_id', 'brokers_application_id_uq');
            }

            if (!Schema::hasColumn('brokers', 'stall_id')) {
                $table->foreignId('stall_id')->nullable()->after('application_id')->constrained('stalls')->nullOnDelete();
                $table->unique('stall_id', 'brokers_stall_id_uq');
            }

            if (!Schema::hasColumn('brokers', 'suffix')) {
                $table->string('suffix')->nullable()->after('last_name');
            }

            if (!Schema::hasColumn('brokers', 'business_name')) {
                $table->string('business_name')->nullable()->after('suffix');
            }

            if (!Schema::hasColumn('brokers', 'contact_number')) {
                $table->string('contact_number')->nullable()->after('address');
            }

            if (!Schema::hasColumn('brokers', 'broker_status')) {
                $table->string('broker_status')->default('Active')->after('stall_name');
            }

            if (!Schema::hasColumn('brokers', 'approval_date')) {
                $table->date('approval_date')->nullable()->after('broker_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('brokers', function (Blueprint $table) {
            if (Schema::hasColumn('brokers', 'approval_date')) {
                $table->dropColumn('approval_date');
            }

            if (Schema::hasColumn('brokers', 'broker_status')) {
                $table->dropColumn('broker_status');
            }

            if (Schema::hasColumn('brokers', 'contact_number')) {
                $table->dropColumn('contact_number');
            }

            if (Schema::hasColumn('brokers', 'business_name')) {
                $table->dropColumn('business_name');
            }

            if (Schema::hasColumn('brokers', 'suffix')) {
                $table->dropColumn('suffix');
            }

            if (Schema::hasColumn('brokers', 'stall_id')) {
                $table->dropConstrainedForeignId('stall_id');
            }

            if (Schema::hasColumn('brokers', 'application_id')) {
                $table->dropConstrainedForeignId('application_id');
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'suffix')) {
                $table->dropColumn('suffix');
            }
        });
    }
};
