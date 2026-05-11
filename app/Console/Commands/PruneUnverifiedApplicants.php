<?php

namespace App\Console\Commands;

use App\Constants\RoleStatusConstant;
use App\Models\User;
use Illuminate\Console\Command;

class PruneUnverifiedApplicants extends Command
{
    protected $signature = 'applicants:prune-unverified {--days=7 : Delete unverified applicant accounts older than this many days}';

    protected $description = 'Delete stale unverified applicant accounts that never confirmed their email address.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $query = User::query()
            ->whereNull('email_verified_at')
            ->where('created_at', '<', $cutoff)
            ->whereHas('roles', function ($roleQuery) {
                $roleQuery->where('role_name', RoleStatusConstant::APPLICANT);
            })
            ->whereDoesntHave('roles', function ($roleQuery) {
                $roleQuery->where('role_name', '!=', RoleStatusConstant::APPLICANT);
            })
            ->doesntHave('brokerApplications');

        $deleted = 0;

        $query->chunkById(100, function ($users) use (&$deleted) {
            foreach ($users as $user) {
                $user->delete();
                $deleted++;
            }
        });

        $this->info("Deleted {$deleted} stale unverified applicant account(s).");

        return self::SUCCESS;
    }
}
