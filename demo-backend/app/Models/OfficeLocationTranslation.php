<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficeLocationTranslation extends Model
{
    protected $fillable = ['office_location_id', 'locale', 'content'];

    protected function casts(): array
    {
        return ['content' => 'array'];
    }

    public function officeLocation()
    {
        return $this->belongsTo(OfficeLocation::class);
    }
}
