<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'additional_expenses',
        'details',
        'white_label_id',
        'chosen_quotation_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'additional_expenses' => 'decimal:2',
    ];

    /**
     * Get all of the quotations for the Project.
     */
    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    /**
     * Get the chosen quotation for the Project.
     */
    public function chosenQuotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class, 'chosen_quotation_id');
    }

}
