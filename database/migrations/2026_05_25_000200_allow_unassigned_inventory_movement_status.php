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
            MODIFY status ENUM('Unassigned', 'In Stock', 'Sold', 'Returned', 'Missing', 'Retired')
            NOT NULL
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('InventoryMovement')
            ->where('status', 'Unassigned')
            ->update(['status' => 'In Stock']);

        DB::statement("
            ALTER TABLE InventoryMovement
            MODIFY status ENUM('In Stock', 'Sold', 'Returned', 'Missing', 'Retired')
            NOT NULL
        ");
    }
};
