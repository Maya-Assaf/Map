<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationPolygonPoint extends Model
{
    protected $fillable = [
        'latitude',
        'longitude',
        'location_polygon_id',
    ];

    public function locationPolygon(): BelongsTo
    {
        return $this->belongsTo(LocationPolygon::class);
    }
}
