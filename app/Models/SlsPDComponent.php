<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlsPDComponent extends Model
{
     use HasFactory;

    protected $table = 'pd_and_sls_comp'; 

    protected $fillable = [
        'component',
        'state_id',
        'slsPD',
        'name',
        'status'
    ];
    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }
}