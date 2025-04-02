<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\VehicleRequest;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
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
                'user_id' => 'required|exists:users,id',
                'plate_number' => 'required|string|max:10',
                'brand' => 'required|string|max:25',
                'model' => 'required|string|max:25',
            ]);

            $data = array_merge($validateData, ['user_id' => $request->input('user_id')]);
            $vehicle = Vehicle::create($data);
            return response()->json([
                'success' => true,
                'message' => '¡Excelente! se agregado un vehículo correctamente.',
                'data' => $vehicle,
            ]);
        } catch (\exception $e) {
            Log::error('Error al adicionar un vehículo' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Ócurrio un error al intentar agregar un vehículo: ' . $e->getMessage()], 500);
        }

    }


    /**
     * Update the specified resource in storage.
     */
    public function update(VehicleRequest $request, Vehicle $vehicle): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            /*            $validateData = $request->validate([
                            'user_id' => 'required|exists:users,id',
                            'plate_number' => 'required|exists:vehicles,id|string|max:10',
                            'brand' => 'required|string|max:25',
                            'model' => 'required|string|max:25',
                        ]);*/

            $updateSuccessful = $vehicle->update($request->only([
                'plate_number',
                'brand',
                'model'
            ]));

            if ($updateSuccessful) {
                return response()->json([
                    'success' => true,
                    'message' => '¡Excelente! se edito el vehículo correctamente.',
                    'data' => $vehicle,
                ]);
            } else {
                Log::warning('Fallo al actualizar el registro.', ['id' => $vehicle->id]);
                return response()->json(['success' => false, 'message' => 'No se pudo actualizar el registro.'], 500);
            }


        } catch (\Exception $e) {
            Log::error('Error actualizando el registro ID ' . $vehicle->id . ': ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Ócurrio un error al intentar editar el registro'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vehicle $vehicle)
    {
        try {
            $vehicle->delete();

            return response()->json(['success' => true, 'message' => '¡Excelente! el registro se ha eliminado correctamente.']);

        } catch (\Exception $e) {
            Log::error('Error eliminando el registro ID ' . $vehicle->id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al intentar eliminar el registro.'], 500);
        }
    }

    public function getVehiclesByUserId(int $userId): JsonResponse
    {

        if (Auth::id() !== $userId) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        try {
            $vehicles = Vehicle::query()->where('user_id', $userId)->get();

            return response()->json($vehicles);
        } catch (\Exception $e) {
            Log::error('Error al intentar obtener los vehiculos del usuario ' . $userId . ': ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Ócurrio un error al intentar obtener los vehículos: ' . $e->getMessage()], 500);
        }

    }
}
