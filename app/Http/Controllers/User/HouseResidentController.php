<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\House;
use App\Models\HouseResident;
use App\Models\WebUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HouseResidentController extends Controller
{
    public function index(House $house): JsonResponse
    {
        $user = Auth::guard('web_user')->user();
        $isRelated = $house->webUsers()->where('web_user_id', $user->id)->exists();

        if (!$user || !$isRelated) {
            return response()->json(['message' => 'No autorizado para acceder a este recurso o no encontrado.'], JsonResponse::HTTP_FORBIDDEN);
        }

        $houseResidents = HouseResident::query()->where('house_id', $house->id)->get();

        return response()->json($houseResidents, 200);

    }
}
