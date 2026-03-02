<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailySanctionHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'daily_sanction_history';

    protected $primaryKey = 'history_id';

    protected $fillable = [
        'daily_sanction_id',
        'financial_year',
        'state_id',
        'ds_date',
        'daily_sanction_no',
        'mother_sanction',
        'ifd_no',
        'sls_name',
        'budget_head',
        'mother_sanction_amount',
        'available_amount',
        'center_share_amount',
        'remark',
        'status',
        'action_type',
        'changed_by',
        'history_timestamp',
        'change_description',
        'old_center_share_amount',
        'new_center_share_amount',
    ];

    protected $casts = [
        'ds_date' => 'date',
        'history_timestamp' => 'datetime',
        'mother_sanction_amount' => 'decimal:2',
        'available_amount' => 'decimal:2',
        'center_share_amount' => 'decimal:2',
        'old_center_share_amount' => 'decimal:2',
        'new_center_share_amount' => 'decimal:2',
        'status' => 'integer',
    ];

    /**
     * Get the daily sanction that owns the history record.
     */
    public function dailySanction()
    {
        return $this->belongsTo(DailySanction::class, 'daily_sanction_id', 'id');
    }

    /**
     * Get the state that owns the history record.
     */
    public function state()
    {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }
}
