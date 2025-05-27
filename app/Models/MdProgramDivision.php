<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MdProgramDivision extends Model
{
    protected $table = 'md_program_divisions';

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'program_division_id', 'md_program_division_id');

    }

}


