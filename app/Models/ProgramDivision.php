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
        'is_active'
    ];

    // Disable updated_at since the table doesn't have it
    public $timestamps = false;
    
    // But we still want to use created_at
    protected $dates = ['created_at'];
}