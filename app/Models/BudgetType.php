<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'white_label_id',
    ];

    /**
     * Get all of the annual budgets for the BudgetType.
     */
    public function annualBudgets(): HasMany
    {
        return $this->hasMany(AnnualBudget::class);
    }
}
