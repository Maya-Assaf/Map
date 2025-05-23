<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreRegisteredUser extends Model
{
    use HasFactory;
     
    protected $fillable = [
        'email' , 'department' , 'position'
    ];
    
}
