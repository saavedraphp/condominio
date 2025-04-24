<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class HousePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'house_id',
        'web_user_id',
        'title',
        'amount',
        'file_path',
        'status',
        'payment_date'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    protected $appends = ['file_path_url'];

    protected function filePathUrl(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                // Verifica si la columna 'file_path' existe y tiene valor
                if (!empty($attributes['file_path']) && Storage::disk('public')->exists($attributes['file_path'])) {
                    // Retorna la URL completa generada por Laravel Storage
                    return Storage::disk('public')->url($attributes['file_path']);
                }
                return null;
            }
        );
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }

    public function webUser(): BelongsTo
    {
        return $this->belongsTo(WebUser::class);
    }
}
