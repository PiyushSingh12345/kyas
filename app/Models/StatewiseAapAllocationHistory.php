<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatewiseAapAllocationHistory extends Model
{
    protected $table = 'statewise_aap_allocation_history';
    protected $guarded = [];
    public $timestamps = false; // we already have manual created_at/updated_at

    protected $casts = [
        'amount' => 'decimal:2',
        'status' => 'integer',
        'history_timestamp' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the state that owns the allocation
     */
    public function state()
    {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }

    /**
     * Get the program division that owns the allocation
     */
    public function programDivision()
    {
        return $this->belongsTo(MdProgramDivision::class, 'pd_id', 'division_id');
    }
}
