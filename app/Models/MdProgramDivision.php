<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MdProgramDivision extends Model
{
    protected $table = 'md_program_divisions';
    
    protected $primaryKey = 'division_id';
    
    public $timestamps = false;

    protected $guarded = [];

    /**
     * Get the status attribute (alias for is_active)
     */
    public function getStatusAttribute()
    {
        return $this->is_active;
    }

    /**
     * Scope to get only active divisions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'program_division_id', 'md_program_division_id');
    }
}


