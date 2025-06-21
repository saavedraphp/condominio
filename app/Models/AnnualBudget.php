<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnnualBudget extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_type_id',
        'year',
        'amount',
        'white_label_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2', // Para asegurar que el monto se trate como decimal
        'year' => 'integer',
    ];

    /**
     * The attributes that should be appended to the model's array form.
     *
     * @var array
     */
    protected $appends = ['spent_amount', 'percentage_spent', 'remaining_amount'];

    /**
     * Get the budget type that owns the annual budget.
     */
    public function budgetType(): BelongsTo
    {
        return $this->belongsTo(BudgetType::class);
    }

    /**
     * Get all of the expenses for the AnnualBudget.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Accessor for the total amount spent from this annual budget.
     */
    protected function spentAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->expenses()->sum('amount')
        );
    }

    /**
     * Accessor for the percentage of the budget that has been spent.
     */
    protected function percentageSpent(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->amount > 0 ? round(($this->spent_amount / $this->amount) * 100, 2) : 0
        );
    }

    /**
     * Accessor for the remaining amount of the budget.
     */
    protected function remainingAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->amount - $this->spent_amount
        );
    }
}
