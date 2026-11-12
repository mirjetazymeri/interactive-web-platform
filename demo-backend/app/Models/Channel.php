<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    protected $fillable = ['name', 'code', 'domain'];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function settings()
    {
        return $this->hasMany(Setting::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_channel');
    }



}
