<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StateReleaseGeneric extends Model
{
    protected $table = 'state_release_generic';
    
    protected $fillable = [
        'allocation_name',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Get the state release data records for this budget head
     */
    public function stateReleaseData(): HasMany
    {
        return $this->hasMany(StateReleaseData::class, 'budget_head_id');
    }
}
