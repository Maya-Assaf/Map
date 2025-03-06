<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZoneImage extends Model
{
    use HasFactory;
    protected $fillable = ['zone_id', 'zone_image_path'];
}
