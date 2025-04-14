<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\House;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            return response()->json(['success' => false, 'message' => 'Error al actualizar los datos datos de la casa'.$e->getMessage()], 500);
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
