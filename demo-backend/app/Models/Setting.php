<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'channel_id',
    ];

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

}
