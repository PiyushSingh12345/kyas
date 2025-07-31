<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReAppropriation extends Model
{
     use HasFactory;

    protected $table = 'reappropriations'; 

    protected $fillable = [
        'financial_year',
        'budget_phase',
        'ro_date',
        'type',
        'section',
        'program_division_id',
        'from_budget_head_id',
        'to_budget_head_id',
        'reappropriation_amount',
        'other_details',
        'entity_type',
        'selected_entity_ids',
        'from_rule',
        'to_rule',
        'reason_for_additionality',
        'proposal_attract_ns_nis',
        'remarks'
    ];
      protected $casts = [
        'selected_entity_ids' => 'array',
        'ro_date' => 'date',
    ];

  
}