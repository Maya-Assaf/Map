<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationSpace extends Model
{
    protected $fillable = [
        'longitude',
        'latitude',
        'radius',
    ];

    public function locatable()
    {
        return $this->morphOne(Location::class, 'locatable');
    }
}
