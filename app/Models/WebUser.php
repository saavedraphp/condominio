<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
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
        'file_path',
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

    protected $appends = ['file_path_url'];

    public function activationToken(): MorphOne
    {
        return $this->morphOne(AccountActivation::class, 'activatable');
    }

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

    public function paymentsMade(): HasMany
    {
        return $this->hasMany(HousePayment::class);
    }

    public function petitions(): HasMany
    {
        return $this->hasMany(Petition::class);
    }

    public function petitionReplies(): MorphMany
    {
        return $this->morphMany(PetitionReply::class, 'repliable');
    }

    protected function filePathUrl(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                if (!empty($attributes['file_path']) && Storage::disk('public')->exists($attributes['file_path'])) {
                    // Retorna la URL completa generada por Laravel Storage
                    return Storage::disk('public')->url($attributes['file_path']);
                }
                return null;
            }
        );
    }
}
