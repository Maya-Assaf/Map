<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubAspect extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'aspect_id'
     ];

    public function aspect()
    {
        return $this->belongsTo(Aspect::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

}
