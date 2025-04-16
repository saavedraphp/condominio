<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserHouseRequest;
use App\Models\House;
use App\Models\WebUser;
use App\Services\House\HouseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UserHouseAssignmentController extends Controller
{
    protected HouseService $houseService;

    public function __construct(HouseService $houseService)
    {
        $this->houseService = $houseService;
    }

    public function index(int $webUser): JsonResponse
    {
        try {
            $assignedHouses = $this->houseService->getAssignedTo($webUser);

            return response()->json($assignedHouses);

        } catch (\Exception $e) {
            Log::error('Error al intentar obtener las casas: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ócurrio un error al intentar obtener las casas: ' . $e->getMessage()
            ], 500);
        }

    }

    public function store(UserHouseRequest $request, WebUser $webUser): JsonResponse
    {
        try {
            $validated = $request->validated();

            $house = House::findOrFail($validated['house_id']);

            // --- Validación Crítica de is_manager ---
            $this->validateManagerAssignment(
                $webUser,
                $house,
                (bool)$validated['is_manager'],
                (bool)$validated['is_resident']
            );

            // Verificar si ya existe la relación
            if ($webUser->houses()->where('house_id', $house->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este usuario ya esta relacinado con esta casa: '
                ], 500);
            }

            DB::transaction(function () use ($webUser, $house, $validated) {
                $webUser->houses()->attach($house->id, [
                    'is_owner' => $validated['is_owner'],
                    'is_resident' => $validated['is_resident'],
                    'is_manager' => $validated['is_manager'],
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! Casa asociada correctamente.',
                'data' => $webUser->houses(),
            ], 201);

        } catch (\exception $e) {
            Log::error('Error al adicionar un usuario' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Ócurrio un error al relacionar el usuario con esta casa: ' . $e->getMessage()], 500);
        }
    }

    public function update(UserHouseRequest $request, WebUser $webUser, House $house): JsonResponse
    {
        $validated = $request->validated();
        try {
            // Pasamos el ID del usuario actual para excluirlo de ciertas comprobaciones
            $this->validateManagerAssignment(
                $webUser,
                $house,
                (bool)$validated['is_manager'],
                (bool)$validated['is_resident'],
                $webUser->id // Excluir a este usuario de la comprobación "otro manager"
            );

            DB::transaction(function () use ($webUser, $house, $validated) {
                $webUser->houses()->updateExistingPivot($house->id, [
                    'is_owner' => $validated['is_owner'],
                    'is_resident' => $validated['is_resident'],
                    'is_manager' => $validated['is_manager'],
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! Asociación altualizada correctamente.',
                'data' => $webUser->houses(),
            ], 201);

        } catch (\exception $e) {
            Log::error('Error al altualizada la asociación ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Ócurrio un error al altualizada la asociación: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Elimina la asociación entre un usuario y una casa.
     */
    public function destroy(WebUser $webUser, House $house): JsonResponse
    {
        try {
            $webUser->houses()->detach($house->id);
            return response()->json([
                'success' => true,
                'message' => '¡Excelente! La asociación fue eliminada correctamente.',
                'data' => $webUser->houses(),
            ], 201);

        } catch (\exception $e) {
            Log::error('Error al eliminar la relación' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Ócurrio un error al intentar eliminar la relación: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Lógica de validación reutilizable para asignar el rol de manager.
     * Lanza una ValidationException si no se cumplen las reglas.
     *
     * @param WebUser $userToAssign El usuario al que se intenta asignar roles.
     * @param House $house La casa en cuestión.
     * @param bool $isTryingToBeManager Si se está intentando poner is_manager = true.
     * @param bool $isTryingToBeResident Si se está intentando poner is_resident = true.
     * @param int|null $excludeUserIdFromCheck ID del usuario a excluir al buscar *otros* managers (útil en updates).
     * @throws ValidationException
     */

    private function validateManagerAssignment(WebUser $userToAssign, House $house, bool $isTryingToBeManager, bool $isTryingToBeResident, ?int $excludeUserIdFromCheck = null)
    {
        if (!$isTryingToBeManager) {
            // Si no se intenta poner como manager, no hay validación específica aquí.
            return;
        }

        // Regla 1: ¿Ya existe OTRO gestor para esta casa?
        $otherManagerQuery = $house->webUsers()->wherePivot('is_manager', true);
        if ($excludeUserIdFromCheck !== null) {
            // Al actualizar, no contamos al usuario actual como "otro" manager
            $otherManagerQuery->where('web_user_id', '!=', $excludeUserIdFromCheck);
        }
        $otherManager = $otherManagerQuery->first();

        if ($otherManager) {
            throw ValidationException::withMessages([
                'is_manager' => "La casa [{$house->name}] ya tiene un gestor asignado (Usuario: {$otherManager->name}). Solo puede haber uno.",
            ]);
        }

        // Regla 2 y 3: Si hay residente, ¿el que intenta ser manager es residente?
        // Buscamos CUALQUIER residente en la casa
        $anyResident = $house->webUsers()->wherePivot('is_resident', true)->first();

        if ($anyResident && !$isTryingToBeResident) {
            // Existe un residente (puede ser este mismo usuario u otro, no importa quién)
            // Y en ESTA operación, se está intentando asignar manager=true SIN asignar resident=true. ¡Inválido!
            throw ValidationException::withMessages([
                'is_manager' => "Si existe un residente en la casa [{$house->name}], solo un residente puede ser el gestor. Debe marcar 'Es Residente' para poder marcar 'Es Gestor'.",
            ]);
        }

        // Si llegamos aquí, la asignación de manager es válida según las reglas.
    }

    public function getUnassigned(int $webUser): JsonResponse
    {
        try {
            $assignedHouses = $this->houseService->getUnassignedOrAssignedToOthers($webUser);

            return response()->json($assignedHouses);

        } catch (\Exception $e) {
            Log::error('Error al intentar obtener las casas: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ócurrio un error al intentar obtener las casas: ' . $e->getMessage()
            ], 500);
        }

    }


}
