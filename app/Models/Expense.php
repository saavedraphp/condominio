<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'annual_budget_id',
        'description',
        'amount',
        'expense_date',
        'file_path',
        'white_label_id',
    ];

    protected $appends = ['file_path_url'];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date', // Para asegurar que se trate como objeto Carbon/Date
    ];

    protected function filePathUrl(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                // Verifica si la columna 'file_path' existe y tiene valor
                if (!empty($attributes['file_path']) && Storage::exists($attributes['file_path'])) {
                    // Retorna la URL completa generada por Laravel Storage
                    return Storage::url($attributes['file_path']);
                }
                return null;
            }
        );
    }

    /**
     * Get the annual budget that owns the expense.
     */
    public function annualBudget(): BelongsTo
    {
        return $this->belongsTo(AnnualBudget::class);
    }
}
