<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetHead extends Model
{
   protected $fillable = ['budget', 'description','category','status'];

    public function budgetPhases()
    {
        return $this->hasMany(BudgetPhase::class);
    }

    public function budgetPhaseHistory()
    {
        return $this->hasMany(BudgetPhaseHistory::class, 'budget_head_id', 'id');
    }

    /**
     * Get the PD wise AAP allocation history for this budget head
     */
    public function pdWiseAapAllocationHistory()
    {
        return $this->hasMany(PdWiseAapAllocationHistory::class, 'bh_id', 'id');
    }

}
