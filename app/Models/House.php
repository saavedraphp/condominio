<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class House extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payment_code',
        'property_unit',
        'address',
        'construction_area',
        'participation_percentage',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('is_resident', 'is_owner', 'is_manager')
            ->withTimestamps();

    }

    public function houseResidents(): HasMany
    {
        return $this->hasMany(HouseResident::class);
    }
}
