<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetPhaseHistory extends Model
{
    protected $table = 'budget_phase_history';
    
    protected $fillable = [
        'budget_phase_id',
        'financial_year',
        'budget_phase',
        'budget_head_id',
        'budget_amount',
        'status',
        'draft_flag',
        'action_type',
        'changed_by',
        'history_timestamp'
    ];

    protected $casts = [
        'budget_amount' => 'decimal:5',
        'status' => 'integer',
        'draft_flag' => 'integer',
        'history_timestamp' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the budget head that owns the history record.
     */
    public function budgetHead()
    {
        return $this->belongsTo(BudgetHead::class, 'budget_head_id', 'id');
    }

    /**
     * Get the user who made the change.
     */
    public function changedByUser()
    {
        return $this->belongsTo(User::class, 'changed_by', 'id');
    }

    /**
     * Get the original budget phase record.
     */
    public function budgetPhase()
    {
        return $this->belongsTo(BudgetPhase::class, 'budget_phase_id', 'id');
    }
}
