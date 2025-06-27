<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FundAllocation extends Model
{
     use HasFactory;

    protected $table = 'fund_allocation'; 

    protected $fillable = [
        'fund_allocation',
        'financial_year',
        'budget_phase',
        'state_id',
        'category', 
        'budget',
        'budget_amount',
        'sls_pd_name',
        'amount',
        'status'
    ];

   
}