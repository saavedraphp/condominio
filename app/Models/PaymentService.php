<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PaymentService extends Model
{
    protected $fillable = [
        'web_user_id',
        'house_id',
        'service_id',
        'quantity',
        'file_path',
        'observations',
        'replace',
        'payment_date',
    ];

    use HasFactory;

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

}
