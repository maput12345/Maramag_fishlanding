<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private array $tableNames = [
        'users' => 'User',
        'roles' => 'Role',
        'user_roles' => 'UserRoleAssignment',
        'employees' => 'Employee',
        'brokers' => 'Broker',
        'buyers' => 'Buyer',
        'fish_types' => 'FishType',
        'broker_fish_type' => 'BrokerFishTypeAssignment',
        'fish_prices' => 'FishPriceRecord',
        'fish_boxes' => 'FishBox',
        'fish_box_purchases' => 'FishBoxStockCycle',
        'fish_inventory' => 'InventoryMovement',
        'sales' => 'SalesTransaction',
        'sales_details' => 'TransactionLineItem',
        'payments' => 'PaymentRecord',
        'financial_statement_entries' => 'FinancialStatementEntry',
        'stalls' => 'Stall',
        'stall_images' => 'StallImage',
        'application_openings' => 'ApplicationOpening',
        'requirement_types' => 'RequirementType',
        'application_opening_requirements' => 'OpeningRequirement',
        'broker_applications' => 'BrokerApplication',
        'application_requirements' => 'SubmittedRequirement',
        'broker_application_review_drafts' => 'ApplicationReviewDraft',
    ];

    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ($this->tableNames as $oldName => $newName) {
            if (Schema::hasTable($oldName) && !Schema::hasTable($newName)) {
                Schema::rename($oldName, $newName);
            }
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach (array_reverse($this->tableNames) as $oldName => $newName) {
            if (Schema::hasTable($newName) && !Schema::hasTable($oldName)) {
                Schema::rename($newName, $oldName);
            }
        }

        Schema::enableForeignKeyConstraints();
    }
};
