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

    public function locatable()
    {
        return $this->morphOne(Location::class, 'locatable');
    }
}
