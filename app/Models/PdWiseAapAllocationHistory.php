<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PdWiseAapAllocationHistory extends Model
{
    protected $table = 'pdwise_aap_allocation_history';
    protected $guarded = [];
    public $timestamps = false; // we already have manual created_at/updated_at

    protected $casts = [
        'amount' => 'decimal:3',
        'status' => 'integer',
        'history_timestamp' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the budget head that owns the allocation
     */
    public function budgetHead()
    {
        return $this->belongsTo(BudgetHead::class, 'bh_id', 'id');
    }

    /**
     * Get the program division that owns the allocation
     */
    public function programDivision()
    {
        return $this->belongsTo(MdProgramDivision::class, 'pd_id', 'division_id');
    }
}
