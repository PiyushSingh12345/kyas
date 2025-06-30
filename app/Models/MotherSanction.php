<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MotherSanction extends Model
{
     use HasFactory;

    protected $table = 'mother_sanction'; 

    protected $fillable = [
        'financial_year',
        'state_id',
        'ms_sequence_no.',
        'file_no',
        'ifd_no', 
        'sanction_date',
        'ky_ms_no',
        'sls_name',
        'pd_component',
        'total_mother_sanction_amount',
        'budget_head',
        'category',
        'available_fund',
        'mother_sanction_amount',
        'uc_received_from_state',
        'signed_copy_of_mother_sanction',
        'last_id',
        'status'
    ];

   public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }
}