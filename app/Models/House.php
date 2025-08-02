<?php

namespace App\Models;

use App\Enums\OwnershipStructure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class House extends Model
{
    use HasFactory, SoftDeletes;
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'payment_code',
        'property_unit',
        'address',
        'construction_area',
        'participation_percentage',
        'ownership_structure',
        'is_lot',
        'opening_balance',
        'is_department',
        'cost_jp',
    ];

    public function webUsers(): BelongsToMany
    {
        return $this->belongsToMany(WebUser::class)
            ->withPivot('is_resident', 'is_owner', 'is_manager')
            ->withTimestamps();

    }

    public function monthlyCharges(): HasMany
    {
        return $this->hasMany(HouseMonthlyCharge::class);
    }

    public function owner()
    {
        return $this->webUsers()->wherePivot('is_owner', true);
    }

    public function owners(): BelongsToMany
    {
        return $this->webUsers()->wherePivot('is_owner', true);
    }

    public function resident()
    {
        return $this->webUsers()->wherePivot('is_resident', true);
    }

    public function HouseResidents(): HasMany
    {
        return $this->hasMany(HouseResident::class);
    }

    public function HouseVehicles(): HasMany
    {
        return $this->hasMany(HouseVehicle::class);
    }


    public function firstResident(): HasOne
    {
        return $this->hasOne(HouseResident::class)->oldestOfMany();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(HousePayment::class);
    }

    public function calculateBalance(): array
    {
        $this->loadMissing([
            'payments:id,house_id,amount,payment_date',
            'monthlyCharges:id,house_id,period_year,period_month,due_date,total_amount,status',
        ]);

        $payments = $this->payments->sum('amount');
        $charges = $this->monthlyCharges()
            ->where('due_date', '<=', Carbon::today())
            ->sum('total_amount');

        return [
            'house_id' => $this->id,
            'amount_paid' => $payments,
            'opening_balance' => $this->opening_balance,
            'amount_due' => ($this->opening_balance + $charges) - $payments,
        ];
    }

    public function getOwnershipStructureDetailsAttribute(): ?array
    {
        $value = $this->ownership_structure;

        if (is_null($value)) {
            return null;
        }

        $enumCase = OwnershipStructure::tryFrom($value);

        if (is_null($enumCase)) {
            return [
                'key' => $value,
                'label' => $value, // O un valor por defecto como 'Desconocido'
            ];
        }

        return [
            'key' => $enumCase->value,
            'label' => $enumCase->label(),
        ];
    }

/*    public function getOwnerAttribute(): ?WebUser
    {
        return $this->owners()->latest('pivot_web_user_house.id')->first();
    }*/

}
