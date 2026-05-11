<?php

use App\Constants\RoleStatusConstant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ApplicantProfile')) {
            return;
        }

        $now = now();
        $applicantUserIds = DB::table('User')
            ->join('UserRoleAssignment', 'UserRoleAssignment.user_id', '=', 'User.id')
            ->join('Role', 'Role.id', '=', 'UserRoleAssignment.role_id')
            ->where('Role.role_name', RoleStatusConstant::APPLICANT)
            ->pluck('User.id')
            ->unique()
            ->values();

        foreach ($applicantUserIds as $userId) {
            $profileExists = DB::table('ApplicantProfile')
                ->where('user_id', $userId)
                ->exists();

            if ($profileExists) {
                continue;
            }

            $user = DB::table('User')->where('id', $userId)->first();
            $application = DB::table('BrokerApplication')
                ->where('user_id', $userId)
                ->orderByDesc('submitted_at')
                ->orderByDesc('id')
                ->first();

            $firstName = trim((string) ($user->first_name ?? '')) ?: ($application->first_name ?? null);
            $middleName = trim((string) ($user->middle_name ?? '')) ?: ($application->middle_name ?? null);
            $lastName = trim((string) ($user->last_name ?? '')) ?: ($application->last_name ?? null);

            if (!$firstName || !$lastName) {
                continue;
            }

            DB::table('ApplicantProfile')->insert([
                'user_id' => $userId,
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'suffix' => trim((string) ($user->suffix ?? '')) ?: ($application->suffix ?? null),
                'contact_number' => trim((string) ($user->contact_number ?? '')) ?: ($application->contact_number ?? null),
                'address' => trim((string) ($user->address ?? '')) ?: ($application->address ?? null),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Keep generated applicant profiles; deleting identity data on rollback is riskier.
    }
};
