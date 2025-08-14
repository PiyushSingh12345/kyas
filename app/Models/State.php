<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    protected $fillable = ['name', 'description', 'budgethead_fourdigits'];

    /**
     * Get the state_id attribute (alias for id)
     */
    public function getStateIdAttribute()
    {
        return $this->id;
    }

    /**
     * Get the state_name attribute (alias for name)
     */
    public function getStateNameAttribute()
    {
        return $this->name;
    }

    /**
     * Get the state_id attribute (alias for id) for JSON serialization
     */
    public function toArray()
    {
        $array = parent::toArray();
        $array['state_id'] = $this->id;
        $array['state_name'] = $this->name;
        return $array;
    }
}
