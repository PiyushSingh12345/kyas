<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgencyReleaseTSA extends Model
{
    use HasFactory;

    protected $table = 'agency_release_tsa';

    protected $fillable = [
        'sanction_number',
        'date',
        'budget_head',
        'purpose_of_grant',
        'program_division_id',
        'amount',
        'central_implementing_agency',
        'status'
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'status' => 'boolean'
    ];

    public function programDivision()
    {
        return $this->belongsTo(MdProgramDivision::class, 'program_division_id', 'division_id');
    }
}

