<?php

namespace Database\Seeders;

use App\Models\RequirementType;
use Illuminate\Database\Seeder;

class RequirementTypeSeeder extends Seeder
{
    public function run(): void
    {
        RequirementType::ensureOfficialChecklistTypesExist();
    }
}
