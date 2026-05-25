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
            ALTER TABLE FishBox
            MODIFY box_status ENUM('Unassigned', 'In Stock', 'Sold', 'Returned', 'Missing', 'Retired')
            NOT NULL DEFAULT 'In Stock'
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('FishBox')
            ->where('box_status', 'Retired')
            ->update(['box_status' => 'Unassigned']);

        DB::statement("
            ALTER TABLE FishBox
            MODIFY box_status ENUM('Unassigned', 'In Stock', 'Sold', 'Returned', 'Missing')
            NOT NULL DEFAULT 'In Stock'
        ");
    }
};
