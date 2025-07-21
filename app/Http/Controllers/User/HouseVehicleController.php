<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\House;
use App\Models\HouseVehicle;
use App\Models\WebUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HouseVehicleController extends Controller
{
    public function index(House $house): JsonResponse
    {
        try {
            $vehicles = HouseVehicle::query()->where('house_id', $house->id)->get();

            return response()->json($vehicles);
        } catch (\Exception $e) {
            Log::error('Error al intentar obtener los vehiculos de la casa ' . $house->id . ': ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Ócurrio un error al intentar obtener los vehículos: ' . $e->getMessage()], 500);
        }
    }

}
