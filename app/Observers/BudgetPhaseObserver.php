<?php

namespace App\Observers;

use App\Models\BudgetPhase;
use App\Models\BudgetPhaseHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BudgetPhaseObserver
{
    /**
     * Handle the BudgetPhase "created" event.
     */
    public function created(BudgetPhase $budgetPhase): void
    {
        // Create history record for new budget phase
        $this->createHistoryRecord($budgetPhase, 'CREATE');
    }

    /**
     * Handle the BudgetPhase "updating" event.
     */
    public function updating(BudgetPhase $budgetPhase): void
    {
        // Only create history record if budget_amount was changed
        if ($budgetPhase->isDirty('budget_amount')) {
            // Get the original values before the update
            $originalValues = $budgetPhase->getOriginal();
            
            // Create history record with original values
            $this->createHistoryRecordWithOriginalValues($budgetPhase, 'UPDATE', $originalValues);
        }
    }

    /**
     * Handle the BudgetPhase "updated" event.
     */
    public function updated(BudgetPhase $budgetPhase): void
    {
        // This method is kept for any post-update logic if needed
    }

    /**
     * Handle the BudgetPhase "deleted" event.
     */
    public function deleted(BudgetPhase $budgetPhase): void
    {
        // Create history record for deleted budget phase
        $this->createHistoryRecord($budgetPhase, 'DELETE');
    }

    /**
     * Handle the BudgetPhase "restored" event.
     */
    public function restored(BudgetPhase $budgetPhase): void
    {
        //
    }

    /**
     * Handle the BudgetPhase "force deleted" event.
     */
    public function forceDeleted(BudgetPhase $budgetPhase): void
    {
        //
    }

    /**
     * Create a history record for the budget phase change
     */
    private function createHistoryRecord(BudgetPhase $budgetPhase, string $actionType): void
    {
        try {
            BudgetPhaseHistory::create([
                'budget_phase_id' => $budgetPhase->id,
                'financial_year' => $budgetPhase->financial_year,
                'budget_phase' => $budgetPhase->budget_phase,
                'budget_head_id' => $budgetPhase->budget_head_id,
                'budget_amount' => $budgetPhase->budget_amount,
                'status' => $budgetPhase->status,
                'draft_flag' => $budgetPhase->draft_flag,
                'action_type' => $actionType,
                'changed_by' => Auth::id() ?? 'system',
                'history_timestamp' => now(),
            ]);
        } catch (\Exception $e) {
            // Log error but don't break the main operation
            \Log::error('Failed to create budget phase history record: ' . $e->getMessage(), [
                'budget_phase_id' => $budgetPhase->id,
                'action_type' => $actionType,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create a history record with original values before update
     */
    private function createHistoryRecordWithOriginalValues(BudgetPhase $budgetPhase, string $actionType, array $originalValues): void
    {
        try {
            BudgetPhaseHistory::create([
                'budget_phase_id' => $budgetPhase->id,
                'financial_year' => $originalValues['financial_year'] ?? $budgetPhase->financial_year,
                'budget_phase' => $originalValues['budget_phase'] ?? $budgetPhase->budget_phase,
                'budget_head_id' => $originalValues['budget_head_id'] ?? $budgetPhase->budget_head_id,
                'budget_amount' => $originalValues['budget_amount'] ?? $budgetPhase->budget_amount,
                'status' => $originalValues['status'] ?? $budgetPhase->status,
                'draft_flag' => $originalValues['draft_flag'] ?? $budgetPhase->draft_flag,
                'action_type' => $actionType,
                'changed_by' => Auth::id() ?? 'system',
                'history_timestamp' => now(),
            ]);
        } catch (\Exception $e) {
            // Log error but don't break the main operation
            \Log::error('Failed to create budget phase history record with original values: ' . $e->getMessage(), [
                'budget_phase_id' => $budgetPhase->id,
                'action_type' => $actionType,
                'original_values' => $originalValues,
                'error' => $e->getMessage()
            ]);
        }
    }
}
