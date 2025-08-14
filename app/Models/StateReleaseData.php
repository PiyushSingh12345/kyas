<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StateReleaseData extends Model
{
    protected $table = 'state_release_data';
    
    protected $fillable = [
        'fy',
        'state_id',
        'SLS_id',
        'budget_head_id',
        'amount',
        'flag',
        'isactive'
    ];

    protected $casts = [
        'amount' => 'decimal:5',
        'flag' => 'boolean',
        'isactive' => 'boolean',
        'state_id' => 'integer',
        'SLS_id' => 'integer',
        'budget_head_id' => 'integer',
    ];

    /**
     * Get the state that owns the release data
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * Get the SLS component that owns the release data
     */
    public function slsComponent(): BelongsTo
    {
        return $this->belongsTo(SlsPDComponent::class, 'SLS_id');
    }

    /**
     * Get the budget head that owns the release data
     */
    public function budgetHead(): BelongsTo
    {
        return $this->belongsTo(StateReleaseGeneric::class, 'budget_head_id');
    }
}
