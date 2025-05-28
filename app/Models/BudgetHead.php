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

}
