<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MotherSanctionHistory extends Model
{
    use HasFactory;

    protected $table = 'mother_sanction_history';

    public $timestamps = false;

    protected $fillable = [
        'mother_sanction_id',
        'financial_year',
        'state_id',
        'ms_sequence_no',
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
        'carry_forward_amount',
        'uc_received_from_State',
        'signed_copy_of_mother_sanction',
        'last_id',
        'status',
        'remark',
        'action_type',
        'changed_by',
        'history_timestamp',
        'change_description',
        'old_mother_sanction_amount',
        'new_mother_sanction_amount',
        'old_available_fund',
        'new_available_fund',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'sanction_date' => 'date',
        'total_mother_sanction_amount' => 'decimal:2',
        'available_fund' => 'decimal:2',
        'mother_sanction_amount' => 'decimal:2',
        'carry_forward_amount' => 'decimal:5',
        'old_mother_sanction_amount' => 'decimal:2',
        'new_mother_sanction_amount' => 'decimal:2',
        'old_available_fund' => 'decimal:2',
        'new_available_fund' => 'decimal:2',
        'status' => 'integer',
    ];

    /**
     * Get the mother sanction that owns the history record.
     */
    public function motherSanction()
    {
        return $this->belongsTo(MotherSanction::class, 'mother_sanction_id', 'id');
    }

    /**
     * Get the state that owns the history record.
     */
    public function state()
    {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }
}

