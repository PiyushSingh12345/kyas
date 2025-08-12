<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatewiseAapAllocation extends Model
{
    use HasFactory;

    protected $table = 'statewise_aap_allocation';

    protected $fillable = [
        'financial_year',
        'state_id',
        'pd_id',
        'amount',
        'status',
        'remark'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'status' => 'integer'
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
