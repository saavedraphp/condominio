<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'annual_budget_id',
        'description',
        'amount',
        'expense_date',
        'white_label_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date', // Para asegurar que se trate como objeto Carbon/Date
    ];

    /**
     * Get the annual budget that owns the expense.
     */
    public function annualBudget(): BelongsTo
    {
        return $this->belongsTo(AnnualBudget::class);
    }
}
