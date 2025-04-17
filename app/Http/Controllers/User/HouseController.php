<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\House;
use App\Models\WebUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HouseController extends Controller
{
    public function getHouse(House $house): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Registro no encontrado'], 404);
        }
        return response()->json($house, 200);
    }

    public function update(Request $request, House $house): JsonResponse
    {
        try {
            $house->update($request->only([
                'property_unit',
                'address',
                'construction_area',
                'participation_percentage'
            ]));

            return response()->json(['success' => true, 'message' => '¡Excelente! La información de la casa se ha actualizado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al actualizar los datos datos de la casa' . $e->getMessage()], 500);
        }
    }

    public function houses(): JsonResponse
    {
        try {
            $userId = Auth::guard('web_user')->id();
            if (!$userId) {
                return response()->json(['success' => false,'message' => 'Registro no encontrado'], 401);
            }

            $user = WebUser::findOrFail($userId);
            $houses = $user->houses;

            return response()->json($houses, 200);
        } catch (\Exception $e) {
            Log::error('Error al intantar obtener las casas asignadas al usuario' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al intantar obtener las casas asignadas al usuario' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
