<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('BrokerApplication', 'broker_apps_user_status_submitted_idx', ['user_id', 'application_status', 'submitted_at']);
        $this->addIndexIfMissing('BrokerApplication', 'broker_apps_batch_status_submitted_idx', ['opening_batch_id', 'application_status', 'submitted_at']);
        $this->addIndexIfMissing('BrokerApplication', 'broker_apps_revision_review_idx', ['revision_resubmitted_at', 'review_date']);

        $this->addIndexIfMissing('SubmittedRequirement', 'submitted_req_app_status_idx', ['application_id', 'verification_status']);
        $this->addIndexIfMissing('SubmittedRequirement', 'submitted_req_app_uploaded_idx', ['application_id', 'uploaded_at']);

        $this->addIndexIfMissing('ApplicationOpening', 'app_openings_batch_status_idx', ['opening_batch_id', 'opening_status']);
        $this->addIndexIfMissing('OpeningRequirement', 'opening_req_opening_sort_idx', ['application_opening_id', 'sort_order']);
        $this->addIndexIfMissing('Stall', 'stalls_status_number_idx', ['stall_status', 'stall_number']);
    }

    public function down(): void
    {
        $this->dropIndexIfExists('Stall', 'stalls_status_number_idx');
        $this->dropIndexIfExists('OpeningRequirement', 'opening_req_opening_sort_idx');
        $this->dropIndexIfExists('ApplicationOpening', 'app_openings_batch_status_idx');

        $this->dropIndexIfExists('SubmittedRequirement', 'submitted_req_app_uploaded_idx');
        $this->dropIndexIfExists('SubmittedRequirement', 'submitted_req_app_status_idx');

        $this->dropIndexIfExists('BrokerApplication', 'broker_apps_revision_review_idx');
        $this->dropIndexIfExists('BrokerApplication', 'broker_apps_batch_status_submitted_idx');
        $this->dropIndexIfExists('BrokerApplication', 'broker_apps_user_status_submitted_idx');
    }

    private function addIndexIfMissing(string $table, string $indexName, array $columns): void
    {
        if (!$this->canUseIndex($table, $indexName, $columns)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($columns, $indexName) {
            $table->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (!Schema::hasTable($table) || !$this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($indexName) {
            $table->dropIndex($indexName);
        });
    }

    private function canUseIndex(string $table, string $indexName, array $columns): bool
    {
        if (!Schema::hasTable($table) || $this->indexExists($table, $indexName)) {
            return false;
        }

        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    private function indexExists(string $table, string $indexName): bool
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('{$table}')"))
                ->contains(fn ($index) => ($index->name ?? null) === $indexName);
        }

        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }
};
