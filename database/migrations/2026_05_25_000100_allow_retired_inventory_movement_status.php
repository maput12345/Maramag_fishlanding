<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("
            ALTER TABLE InventoryMovement
            MODIFY status ENUM('In Stock', 'Sold', 'Returned', 'Missing', 'Retired')
            NOT NULL
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('InventoryMovement')
            ->where('status', 'Retired')
            ->delete();

        DB::statement("
            ALTER TABLE InventoryMovement
            MODIFY status ENUM('In Stock', 'Sold', 'Returned', 'Missing')
            NOT NULL
        ");
    }
};
