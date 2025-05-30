<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetPhase extends Model
{
     use HasFactory;

    protected $table = 'budget_phase'; 

    protected $fillable = [
        'financial_year',
        'budget_phase',
        'budget_head_id',
        'budget_amount',
        'status',
        'draft_flag',
    ];

    public function budgetHead()
    {
        return $this->belongsTo(BudgetHead::class);
    }
}