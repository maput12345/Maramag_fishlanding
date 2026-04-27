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
            ALTER TABLE fish_boxes
            MODIFY box_status ENUM('Unassigned', 'In Stock', 'Sold', 'Returned', 'Missing')
            NOT NULL DEFAULT 'In Stock'
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('fish_boxes')
            ->where('box_status', 'Unassigned')
            ->update(['box_status' => 'In Stock']);

        DB::statement("
            ALTER TABLE fish_boxes
            MODIFY box_status ENUM('In Stock', 'Sold', 'Returned', 'Missing')
            NOT NULL DEFAULT 'In Stock'
        ");
    }
};
