<?php // app/Traits/ManagesCasaSession.php

namespace App\Traits;

use App\Models\House;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session; // O la fachada que uses

trait ManagesHouseSession
{
    /**
     * Elimina el ID de la casa de la sesión.
     *
     * @return void
     */
    protected function clearHouseSession(): void
    {
        if (Session::has('selected_house_id')) {
            Session::forget('selected_house_id');
            Session::forget('selected_house_name');
            Log::info('Session house_id cleared.');
        }
    }

}
