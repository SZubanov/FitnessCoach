<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSize extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'user_id',
        'neck',
        'chest',
        'waist',
        'biceps',
        'pelvis',
        'thigh',
        'tibia',
        'date'
    ];
}
