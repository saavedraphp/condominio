<?php

namespace App\Http\Controllers;

use App\Http\Requests\VehicleRequest;
use App\Models\Vehicle;
use App\Models\WebUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class VehicleController extends Controller
{

    public function showPage(): View
    {
        $webUser = Auth::guard('web_user')->user();
        return view('user.vehicles_list', [
            'isAdmin' => false,
            'user' => $webUser,
        ]);
    }

    public function index(WebUser $webUser)
    {
        try {
            $vehicles = Vehicle::query()->where('web_user_id', $webUser->id)->get();

            return response()->json($vehicles);
        } catch (\Exception $e) {
            Log::error('Error al intentar obtener los vehiculos del usuario ' . $webUser->id . ': ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Ócurrio un error al intentar obtener los vehículos: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validateData = $request->validate([
                'web_user_id' => 'required|exists:web_users,id',
                'plate_number' => 'required|string|max:10',
                'brand' => 'required|string|max:25',
                'model' => 'required|string|max:25',
            ]);

            $vehicle = Vehicle::create($validateData);
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
    public function update(VehicleRequest $request, WebUser $webUser, Vehicle $vehicle): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $updateSuccessful = $vehicle->update($validatedData);

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
    public function destroy(WebUser $webUser, Vehicle $vehicle): JsonResponse
    {
        try {
            $vehicle->delete();

            return response()->json(['success' => true, 'message' => '¡Excelente! el registro se ha eliminado correctamente.']);

        } catch (\Exception $e) {
            Log::error('Error eliminando el registro ID ' . $vehicle->id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al intentar eliminar el registro.'], 500);
        }
    }

    public function getVehicles(): JsonResponse
    {
        $userId = Auth::guard('web_user')->id();

        try {
            $vehicles = Vehicle::query()->where('web_user_id', $userId)->get();

            return response()->json($vehicles);
        } catch (\Exception $e) {
            Log::error('Error al intentar obtener los vehiculos del usuario ' . $userId . ': ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Ócurrio un error al intentar obtener los vehículos: ' . $e->getMessage()], 500);
        }

    }
}
