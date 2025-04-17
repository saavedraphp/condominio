<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class WebUser extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    protected $guard_name = 'web_user';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'status'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function houses(): BelongsToMany
    {
        return $this->belongsToMany(House::class, 'house_web_user')
        ->withPivot('is_resident', 'is_owner', 'is_manager')
            ->withTimestamps();
    }

    public function ownedHouses()
    {
        return $this->houses()->wherePivot('is_owner', true);
    }

    public function rentedHouses()
    {
        return $this->houses()->wherePivot('is_resident', true);
    }
}
