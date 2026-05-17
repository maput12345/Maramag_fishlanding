<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $duplicate = DB::table('RequirementType')
                ->where('requirement_name', 'Community Fax Certificate')
                ->first();

            if (!$duplicate) {
                return;
            }

            $replacement = DB::table('RequirementType')
                ->where('requirement_name', 'Community Tax Certificate')
                ->first()
                ?: DB::table('RequirementType')
                    ->where('requirement_name', 'Barangay Clearance and Community Tax Certificate')
                    ->first();

            if (!$replacement) {
                DB::table('OpeningRequirement')
                    ->where('requirement_type_id', $duplicate->id)
                    ->delete();

                DB::table('SubmittedRequirement')
                    ->where('requirement_type_id', $duplicate->id)
                    ->update(['requirement_type_id' => null]);

                DB::table('RequirementType')
                    ->where('id', $duplicate->id)
                    ->delete();

                return;
            }

            DB::table('OpeningRequirement')
                ->where('requirement_type_id', $duplicate->id)
                ->orderBy('id')
                ->get()
                ->each(function ($openingRequirement) use ($replacement) {
                    $alreadyExists = DB::table('OpeningRequirement')
                        ->where('application_opening_id', $openingRequirement->application_opening_id)
                        ->where('requirement_type_id', $replacement->id)
                        ->exists();

                    if ($alreadyExists) {
                        DB::table('OpeningRequirement')
                            ->where('id', $openingRequirement->id)
                            ->delete();
                        return;
                    }

                    DB::table('OpeningRequirement')
                        ->where('id', $openingRequirement->id)
                        ->update(['requirement_type_id' => $replacement->id]);
                });

            DB::table('SubmittedRequirement')
                ->where('requirement_type_id', $duplicate->id)
                ->update(['requirement_type_id' => $replacement->id]);

            DB::table('RequirementType')
                ->where('id', $duplicate->id)
                ->delete();
        });
    }

    public function down(): void
    {
        // Intentionally left blank. This migration removes a misspelled duplicate.
    }
};
