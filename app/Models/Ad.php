<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ad extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description', 'start_day', 'end_day', 'active'];

    protected $casts = [
        'active' => 'boolean',
        'start_day' => 'date',
        'end_day' => 'date',
    ];
}
