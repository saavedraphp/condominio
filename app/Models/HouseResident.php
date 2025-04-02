<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HouseResident extends Model
{
    use HasFactory;

    protected $fillable = [
        'house_id',
        'user_id',
        'name',
        'email',
        'phone',
        'brith_date',

    ];
}
