<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationPath extends Model
{
    protected $with = ['points'];

    public function points()
    {
        return $this->hasMany(LocationPathPoint::class);
    }

    public function locatable()
    {
        return $this->morphOne(Location::class, 'locatable');
    }

}
