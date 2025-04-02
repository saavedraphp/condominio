<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\HouseResident;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HouseResidentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validateData = $request->validate([
                'house_id' => 'required|exists:houses,id',
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'email' => 'required|email'
            ]);
            $userId = Auth::id();
            $resident = HouseResident::create(array_merge($validateData, ['user_id' => $userId]));
            return response()->json([
                'success' => true,
                'message' => '¡Excelente! se agregado un integrante correctamente.',
                'data' => $resident,
            ]);
        } catch (\exception $e) {
            return response()->json(['success' => false, 'message' => 'Ócurrio un error al intentar agregar un integrante: ' . $e->getMessage()], 500);
        }

    }

    public function getHouseResidentsData(int $houseId): JsonResponse
    {
        $user = Auth::id();

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $houseResidents = HouseResident::query()->where('house_id', $houseId)->get();
        return response()->json($houseResidents, 200);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, HouseResident $houseResident): JsonResponse
    {
        try {
            $validateData = $request->validate([
                'house_id' => 'required|exists:houses,id',
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'email' => 'required|email'
            ]);
            $userId = Auth::id();
            $dataToUpdate = array_merge($validateData, ['user_id' => $userId]);

            $updateSuccessful = $houseResident->update($dataToUpdate);

            if ($updateSuccessful) {
                return response()->json([
                    'success' => true,
                    'message' => '¡Excelente! se edito el integrante correctamente.',
                    'data' => $houseResident,
                ]);
            } else {
                Log::warning('Fallo al actualizar HouseResident sin excepción.', ['id' => $houseResident->id]);
                return response()->json(['success' => false, 'message' => 'No se pudo actualizar el residente.'], 500);
            }


        } catch (\Exception $e) {
            Log::error('Error actualizando HouseResident ID ' . $houseResident->id . ': ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Ócurrio un error al intentar editar el integrante: '], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HouseResident $houseResident)
    {
        try {
            $houseResident->delete();

            return response()->json(['success' => true, 'message' => 'Residente eliminado correctamente.']);

        } catch (\Exception $e) {
            Log::error('Error eliminando residente ID ' . $houseResident->id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al eliminar el residente.'], 500); // Error 500 Internal Server Error
        }
    }
}
