<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\VehicleRequest;
use App\Models\House;
use App\Models\HouseVehicle;
use App\Models\WebUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class HouseVehicleController extends Controller
{
    public function index(WebUser $webUser, House $house): JsonResponse
    {
        try {
            $houseId = $house->getKey();
            $isAuthorized = $webUser->houses()
                ->whereKey($houseId)
                ->exists();

            if (!$isAuthorized) {
                return response()->json(['message' => 'Acceso denegado: El usuario no tiene permisos para este registro.'], 403);
            }

            $vehicles = HouseVehicle::query()->where('house_id', $houseId)->get();

            return response()->json($vehicles);
        } catch (\Exception $e) {
            Log::error('Error al intentar obtener los vehiculos: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Ócurrio un error al intentar obtener los vehículos'], 500);
        }
    }

    public function store(VehicleRequest $request): JsonResponse
    {
        try {
            $validateData = $request->validated();

            $vehicle = HouseVehicle::create($validateData);
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

    public function update(VehicleRequest $request, WebUser $webUser, House $house, HouseVehicle $house_vehicle): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $updateSuccessful = $house_vehicle->update($validatedData);

            if ($updateSuccessful) {
                return response()->json([
                    'success' => true,
                    'message' => '¡Excelente! se edito el vehículo correctamente.',
                    'data' => $house_vehicle,
                ]);
            } else {
                Log::warning('Fallo al actualizar el registro.', ['id' => $house_vehicle->id]);
                return response()->json(['success' => false, 'message' => 'No se pudo actualizar el registro.'], 500);
            }


        } catch (\Exception $e) {
            Log::error('Error actualizando el registro ID ' . $house_vehicle->id . ': ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Ócurrio un error al intentar editar el registro'], 500);
        }
    }

    public function destroy(WebUser $webUser, House $house, HouseVehicle $houseVehicle): JsonResponse
    {
        try {
            $houseVehicle->delete();

            return response()->json(['success' => true, 'message' => '¡Excelente! el registro se ha eliminado correctamente.']);

        } catch (\Exception $e) {
            Log::error('Error eliminando el registro ID ' . $houseVehicle->id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al intentar eliminar el registro.'], 500);
        }
    }
}
