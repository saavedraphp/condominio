<?php

namespace App\Observers;

use App\Models\House;
use App\Models\HousePayment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;


class HousePaymentObserver
{
    /**
     * Handle the HousePayment "created" event.
     */
    public function created(HousePayment $housePayment): void
    {
        Log::info("PaymentObserver 'created' se ha disparado para el pago ID: " . $housePayment->id);
        $this->invalidateOwnerDebtCache($housePayment->house);
    }

    /**
     * Handle the HousePayment "updated" event.
     */
    public function updated(HousePayment $housePayment): void
    {
        $this->invalidateOwnerDebtCache($housePayment->house);
    }

    /**
     * Handle the HousePayment "deleted" event.
     */
    public function deleted(HousePayment $housePayment): void
    {
        $this->invalidateOwnerDebtCache($housePayment->house);
    }

    /**
     * Handle the HousePayment "restored" event.
     */
    public function restored(HousePayment $housePayment): void
    {
        $this->invalidateOwnerDebtCache($housePayment->house);
    }

    /**
     * Handle the HousePayment "force deleted" event.
     */
    public function forceDeleted(HousePayment $housePayment): void
    {
        $this->invalidateOwnerDebtCache($housePayment->house);
    }

    protected function invalidateOwnerDebtCache(?House $house): void
    {
        // Si por alguna razón la casa no está asociada, no hacemos nada.
        if (!$house) {
            Log::warning("PaymentObserver: El pago no tenía casa asociada. No se invalidó ningún caché.");
            return;
        }

        // Cargamos los propietarios y limpiamos el caché para cada uno.
        foreach ($house->owners as $owner) {
            $cacheKey = "user.{$owner->id}.total_debt";
            Log::info("PaymentObserver: Borrando caché para el usuario ID {$owner->id} con la clave: " . $cacheKey);
            Cache::forget($cacheKey);
        }
    }

}
