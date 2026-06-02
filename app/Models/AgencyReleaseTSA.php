<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgencyReleaseTSA extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'agency_release_tsa';

    protected $fillable = [
        'sanction_number',
        'date',
        'budget_head',
        'purpose_of_grant',
        'program_division_id',
        'amount',
        'expenditure',
        'central_implementing_agency',
        'is_ner',
        'status'
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'expenditure' => 'decimal:2',
        'is_ner' => 'boolean',
        'status' => 'boolean'
    ];

    public function programDivision()
    {
        return $this->belongsTo(MdProgramDivision::class, 'program_division_id', 'division_id');
    }
}

