<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PdwiseAapAllocation extends Model
{
    use HasFactory;

    protected $table = 'pdwise_aap_allocation';

    protected $fillable = [
        'financial_year',
        'bh_id',
        'pd_id',
        'amount',
        'status',
        'remark'
    ];

    protected $casts = [
        'amount' => 'decimal:3',
        'status' => 'integer'
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
