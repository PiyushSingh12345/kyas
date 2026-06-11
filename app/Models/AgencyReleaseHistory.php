<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgencyReleaseHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'agency_release_history';

    protected $primaryKey = 'history_id';

    protected $fillable = [
        'release_type',
        'release_id',
        'sanction_number',
        'date',
        'budget_head',
        'purpose_of_grant',
        'program_division_id',
        'amount',
        'expenditure',
        'central_implementing_agency',
        'ut',
        'agency_vendor',
        'status',
        'action_type',
        'changed_by',
        'history_timestamp',
        'change_description',
    ];

    protected $casts = [
        'date' => 'date',
        'history_timestamp' => 'datetime',
        'amount' => 'decimal:2',
        'expenditure' => 'decimal:2',
        'status' => 'integer',
    ];

    public function programDivision()
    {
        return $this->belongsTo(MdProgramDivision::class, 'program_division_id', 'division_id');
    }
}

