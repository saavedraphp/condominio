<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\HouseRequest;
use App\Models\House;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class HouseController extends Controller
{
    public function showListPage(): View
    {
        return view('admin.houses.houses');
    }

    public function index(): JsonResponse
    {

        try {
            $houses = House::all();
            return response()->json($houses);
        } catch (\Exception $e) {
            Log::error('Error al intentar obtener las casas: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Ócurrio un error al intentar obtener las casas: ' . $e->getMessage()], 500);
        }

    }

    public function store(HouseRequest $request): JsonResponse
    {
        try {
            $validateData = $request->validated();

            $house = House::create($validateData);

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! Se agrego una casa correctamente.',
                'data' => $house
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al adicionar un casa' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al adicionar los datos de la casa' . $e->getMessage()
            ], 500);
        }
    }

    public function update(HouseRequest $request, House $house): JsonResponse
    {
        try {
            $validateData = $request->validated();

            $result = $house->update($validateData);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => '¡Excelente! Se edito el registro correctamente.',
                    'data' => $house,
                ], 201);
            } else {
                Log::warning('Fallo al actualizar el registro.', ['id' => $house->id]);
                return response()->json(['success' => false, 'message' => 'No se pudo actualizar el registro.'], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error al editar un casa' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al editar los datos de la casa' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(House $house): JsonResponse
    {
        try {
            $house->delete();

            return response()->json(['success' => true, 'message' => '¡Excelente! el registro se ha eliminado correctamente.']);

        } catch (\Exception $e) {
            Log::error('Error eliminando el registro ID ' . $house->id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al intentar eliminar el registro.'], 500);
        }
    }
}
