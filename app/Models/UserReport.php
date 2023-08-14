<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'user_id',
        'calories',
        'protein',
        'fat',
        'carbohydrate',
        'unit',
        'date'
    ];
}
