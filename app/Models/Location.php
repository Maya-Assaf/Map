<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;



    protected $fillable = [
        'name',
        'user_id',
        'description',
        'aspect_id',
        'sub_aspect_id',
        'category_id',
    ];


    public function aspect()
        {
            return $this->belongsTo(Aspect::class);
        }

    public function subAspect()
        {
            return $this->belongsTo(SubAspect::class);
        }

    public function category()
        {
            return $this->belongsTo(Category::class);
        }

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
    public function locatable()
    {
        return $this->morphTo();
    }

}
