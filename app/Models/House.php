<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class House extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_unit',
        'address',
        'construction_area',
        'participation_percentage',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);

    }
}
