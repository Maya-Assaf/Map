<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationPathPoint extends Model
{
    protected $fillable = [
        'latitude',
        'longitude',
        'location_path_id',
    ];

    protected $casts = [
        'latitude' => 'double',
        'longitude' => 'double',
    ];

    public function locationPath(): BelongsTo
    {
        return $this->belongsTo(LocationPath::class);
    }
}
