<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessLevel extends Model
{
    protected $fillable = ['name', 'description'];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'access_level_permission');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_access_level');
    }

}
