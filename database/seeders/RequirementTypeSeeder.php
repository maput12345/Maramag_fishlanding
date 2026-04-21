<?php

namespace Database\Seeders;

use App\Models\RequirementType;
use Illuminate\Database\Seeder;

class RequirementTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (RequirementType::officialChecklistDefinitions() as $requirement) {
            RequirementType::updateOrCreate(
                ['requirement_name' => $requirement['requirement_name']],
                [
                    'is_required' => $requirement['is_required'],
                    'description' => $requirement['description'],
                ]
            );
        }
    }
}
