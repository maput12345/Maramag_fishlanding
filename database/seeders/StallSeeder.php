<?php

namespace Database\Seeders;

use App\Models\Stall;
use Illuminate\Database\Seeder;

class StallSeeder extends Seeder
{
    public function run(): void
    {
        foreach (range(1, 7) as $stallNumber) {
            Stall::firstOrCreate(
                ['stall_number' => (string) $stallNumber],
                ['stall_status' => 'Vacant']
            );
        }
    }
}
