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
        'ownership_structure',
        'is_lot',
        'opening_balance',
        'is_department',
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

    public function calculateBalance(): array
    {
        $this->loadMissing([
            'payments:id,house_id,amount,payment_date',
            'monthlyCharges:id,house_id,period_year,period_month,total_amount,status',
        ]);

        $payments = $this->payments->sum('amount');
        $charges = $this->monthlyCharges->sum('total_amount');

        return [
            'house_id' => $this->id,
            'amount_paid' => $payments,
            'opening_balance' => $this->opening_balance,
            'amount_due' => ($this->opening_balance + $charges) - $payments,
        ];
    }

}
