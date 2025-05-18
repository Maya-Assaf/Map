<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;

    protected $fillable = [
        'old_position',
        'new_position',
        'affected_user_id',
        'updated_by_user_id',
    ];

    public function affectedUser()
{
    return $this->belongsTo(User::class, 'affected_user_id');
}

public function updatedBy()
{
    return $this->belongsTo(User::class, 'updated_by_user_id');
}

}
