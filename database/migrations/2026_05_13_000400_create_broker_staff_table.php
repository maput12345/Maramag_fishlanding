<?php

use App\Constants\RoleStatusConstant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('BrokerStaff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('broker_id')->constrained('Broker')->cascadeOnDelete();
            $table->foreignId('user_id')->unique()->constrained('User')->cascadeOnDelete();
            $table->string('position')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['broker_id', 'status']);
        });

        DB::table('Role')->updateOrInsert(
            ['role_name' => RoleStatusConstant::CASHIER],
            [
                'description' => 'Cashier staff role',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('BrokerStaff');

        DB::table('Role')
            ->where('role_name', RoleStatusConstant::CASHIER)
            ->delete();
    }
};
