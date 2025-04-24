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

    public function webUsers(): BelongsToMany
    {
        return $this->belongsToMany(WebUser::class)
            ->withPivot('is_resident', 'is_owner', 'is_manager')
            ->withTimestamps();

    }

    public function owner()
    {
        return $this->webUsers()->wherePivot('is_owner', true);
    }

    public function resident()
    {
        return $this->webUsers()->wherePivot('is_resident', true);
    }

    public function residents(): HasMany
    {
        return $this->hasMany(HouseResident::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(HousePayment::class);
    }

/*    public function owner(): BelongsTo
    {
        // Asume que tienes una columna 'current_owner_id' en la tabla 'houses'
        return $this->belongsTo(WebUser::class, 'current_owner_id');
    }*/
}
