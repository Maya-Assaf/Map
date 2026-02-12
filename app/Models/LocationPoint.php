<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'longitude',
        'latitude'
    ];

    protected $casts = [
        'longitude' => 'double',
        'latitude' => 'double'
    ];

    public function locatable()
    {
        return $this->morphOne(Location::class, 'locatable');
    }
}
