<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var string[]
     */
    private array $profileColumns = [
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'contact_number',
        'address',
    ];

    public function up(): void
    {
        Schema::table('User', function (Blueprint $table) {
            foreach ($this->profileColumns as $column) {
                if (Schema::hasColumn('User', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('User', function (Blueprint $table) {
            if (!Schema::hasColumn('User', 'first_name')) {
                $table->string('first_name')->nullable()->after('status');
            }

            if (!Schema::hasColumn('User', 'middle_name')) {
                $table->string('middle_name')->nullable()->after('first_name');
            }

            if (!Schema::hasColumn('User', 'last_name')) {
                $table->string('last_name')->nullable()->after('middle_name');
            }

            if (!Schema::hasColumn('User', 'suffix')) {
                $table->string('suffix', 50)->nullable()->after('last_name');
            }

            if (!Schema::hasColumn('User', 'contact_number')) {
                $table->string('contact_number', 50)->nullable()->after('suffix');
            }

            if (!Schema::hasColumn('User', 'address')) {
                $table->text('address')->nullable()->after('contact_number');
            }
        });
    }
};
