<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationPolygon extends Model
{
    protected $with = ['points'];

    public function points()
    {
        return $this->hasMany(LocationPolygonPoint::class);
    }

    public function locatable()
    {
        return $this->morphOne(Location::class, 'locatable');
    }

}
