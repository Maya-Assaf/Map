<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;
    public function user() {
        return $this->belongsTo(User::class);
    }

    public function images()
    {
        return $this->hasMany(LocationImage::class);
    }

    public function references()
    {
        return $this->hasMany(LocationReference::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    protected $fillable = [
        'name',
        'category',
        'user_id',
        'longitude',
        'latitude',
        'description'
    ];



}
