<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationReference extends Model
{
    protected $fillable = ['location_id', 'pdf_path'];
}
