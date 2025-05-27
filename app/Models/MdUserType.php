<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MdUserType extends Model
{
    protected $table = 'md_user_types';

    protected $guarded = [];

    // create belong to relationship with md_user_type model
    public function userType()
    {
        return $this->belongsTo(User::class, 'user_type_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'user_type_id', 'md_user_type_id');
    }


}
