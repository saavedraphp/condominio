<?php

namespace App\Services;

use App\Models\WebUser;
use App\Models\House;
use Illuminate\Support\Facades\Cache;

class UserDebtService
{
    /**
     * Calcula la deuda total de un usuario a través de todas las casas que posee.
     *
     * @param WebUser $user
     * @return float
     */
    public function calculateTotalDebt(WebUser $user): float
    {
        // 1. Obtenemos las casas del usuario donde es propietario.
        // 2. MUY IMPORTANTE: Usamos `with()` para evitar el problema N+1.
        //    Cargamos de antemano las relaciones 'payments' y 'monthlyCharges'
        //    que el método `calculateBalance` va a necesitar. Esto es clave para el rendimiento.
        $cacheKey = "user.{$user->id}.total_debt";

       return Cache::remember($cacheKey, 60, function () use ($user) {

            $ownedHouses = $user->houses()
                ->wherePivot('is_owner', true)
                ->with('payments', 'monthlyCharges')
                ->get();

            return $ownedHouses->sum(function (House $house) {
                return $house->calculateBalance()['amount_due'];
            });
       });
    }
}
