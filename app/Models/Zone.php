<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'user_id',
        'longitude',
        'latitude',
        'radius'
    ];

    
    public function ZoneImages()
    {
        return $this->hasMany(ZoneImage::class);
    }

    public function ZoneReferences()
    {
        return $this->hasMany(ZoneReference::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

}
