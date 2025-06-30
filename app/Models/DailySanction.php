<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailySanction extends Model
{
     use HasFactory;

    protected $table = 'daily_sanction'; 

    protected $fillable = [
        'financial_year',
        'state_id',
        'ds_date',
        'mother_sanction',
        'ifd_no', 
        'sls_name',
        'budget_head',
        'mother_sanction_amount',
        'available_amount',
        'center_share_amount',
        'status'
    ];

     public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

   
}