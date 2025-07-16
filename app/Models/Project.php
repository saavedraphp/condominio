<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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
        'file_path',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'additional_expenses' => 'decimal:2',
    ];

    protected $appends = ['file_path_url'];

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
