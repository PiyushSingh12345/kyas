<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramDivision extends Model
{
    use HasFactory;

    protected $table = 'md_program_divisions';
    protected $primaryKey = 'division_id';
    
    protected $fillable = [
        'division_name',
        'is_active',
        'created_at'
    ];

    // Only use created_at, not updated_at
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;
    
    protected $dates = ['created_at'];
}