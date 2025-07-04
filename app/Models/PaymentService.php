<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PaymentService extends Model
{
    use HasFactory;

    protected $fillable = [
        'web_user_id',
        'house_id',
        'service_id',
        'quantity',
        'consumption',
        'file_path',
        'observations',
        'replace',
        'payment_date',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'replace' => 'boolean',
    ];


    protected $appends = [
        'file_path_url',
        'consumption_calculated',
    ];

    protected function consumptionCalculated(): Attribute
    {
        return new Attribute(
            get: function ($value, $attributes) {
                // Busca el registro de pago anterior para la misma casa y servicio.
                $previousPayment = self::where('house_id', $attributes['house_id'])
                    ->where('service_id', $attributes['service_id'])
                    ->where('payment_date', '<', $attributes['payment_date'])
                    ->orderBy('payment_date', 'desc')
                    ->first();

                // Si no hay un pago anterior, el consumo es 0 (o la propia lectura si es el primer registro).
                if (!$previousPayment) {
                    return 0; // O podrías devolver null para indicar que no es calculable.
                }

                // Calcula el consumo: lectura actual - lectura anterior.
                return $attributes['quantity'] - $previousPayment->quantity;
            }
        );
    }

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
