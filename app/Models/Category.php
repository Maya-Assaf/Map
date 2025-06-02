<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sub_aspect_id'
     ];

    public function subAspect()
    {
        return $this->belongsTo(SubAspect::class);
    }

}
