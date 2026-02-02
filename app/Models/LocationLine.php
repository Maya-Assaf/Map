<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationLine extends Model
{
    protected $fillable = [
        'longitude_a',
        'latitude_a',
        'longitude_b',
        'latitude_b',
    ];

    public function locatable()
    {
        return $this->morphOne(Location::class, 'locatable');
    }
}
