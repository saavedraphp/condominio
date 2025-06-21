<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChargeDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'house_monthly_charge_id',
        'parent_detail_id',
        'item_description',
        'amount',
        'item_type_code',
        'calculation_snapshot',
    ];

    protected $casts = [
        'calculation_snapshot' => 'array', // O 'object' si prefieres stdClass
        'amount' => 'decimal:2',
    ];

    /**
     * Get the monthly charge that owns this detail.
     */
    public function monthlyCharge(): BelongsTo
    {
        return $this->belongsTo(HouseMonthlyCharge::class, 'house_monthly_charge_id');
    }

    /**
     * Get the parent detail item if this is a sub-item.
     */
    public function parentDetail(): BelongsTo
    {
        return $this->belongsTo(ChargeDetail::class, 'parent_detail_id');
    }

    /**
     * Get the child detail items (sub-items) if this is a parent item.
     */
    public function childDetails(): HasMany
    {
        return $this->hasMany(ChargeDetail::class, 'parent_detail_id');
    }

    /**
     * Scope a query to only include top-level details.
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_detail_id');
    }

    /**
     * Scope a query to only include sub-details (children).
     */
    public function scopeSubDetails($query)
    {
        return $query->whereNotNull('parent_detail_id');
    }
}
