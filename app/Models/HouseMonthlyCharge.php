<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HouseMonthlyCharge extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'house_id',
        'period_year',
        'period_month',
        'total_amount',
        'status',
        'pdf_path',
        'issued_date',
        'due_date',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the house that owns this monthly charge.
     */
    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }

    /**
     * Get all of the charge details for the monthly charge.
     */
    public function details(): HasMany
    {
        return $this->hasMany(ChargeDetail::class);
    }

    /**
     * Get only the top-level charge details (items that are not sub-items).
     */
    public function topLevelDetails(): HasMany
    {
        return $this->hasMany(ChargeDetail::class)->whereNull('parent_detail_id');
    }

    /**
     * Recalculate and save the total_amount based on its top-level details.
     * Call this method after adding/removing/updating details.
     */
    public function updateTotalAmount()
    {
        // Suma solo los amounts de los detalles que no son hijos de otros
        $this->total_amount = $this->topLevelDetails()->sum('amount');
        $this->save();
    }
}
