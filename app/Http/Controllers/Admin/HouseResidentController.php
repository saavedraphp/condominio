<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\House;
use App\Models\HouseResident;
use App\Models\WebUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class HouseResidentController extends Controller
{
    public function showListPage(WebUser $webUser, House $house): View
    {
        return view('admin.users.house_attributes',[
            'webUser' => $webUser,
            'house' => $house,
        ]);
    }
    public function store(Request $request): JsonResponse
    {
        try {
            $validateData = $request->validate([
                'house_id' => 'required|exists:houses,id',
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'email' => 'required|email'
            ]);

            $resident = HouseResident::create($validateData);
            return response()->json([
                'success' => true,
                'message' => '¡Excelente! se agregado un integrante correctamente.',
                'data' => $resident,
            ]);
        } catch (\exception $e) {
            $messageError = "Ócurrio un error al intentar agregar un integrante:";
            Log::error($messageError. ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $messageError], 500);
        }

    }

    public function index(WebUser $webUser, House $house): JsonResponse
    {
        $isAuthorized = $webUser->houses()
            ->whereKey($house->getKey())
            ->exists();

        if (!$isAuthorized) {
            return response()->json(['message' => 'Acceso denegado: El usuario no tiene permisos de propietario o gestor para esta casa.'], 403);
        }

        $houseResidents = HouseResident::query()->where('house_id', $house->id)->get();

        return response()->json($houseResidents, 200);

    }

    public function update(Request $request, WebUser $webUser, House $house, HouseResident $houseResident): JsonResponse
    {

        try {
            $validateData = $request->validate([
                'house_id' => 'required|exists:houses,id',
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'email' => 'required|email'
            ]);

            $updateSuccessful = $houseResident->update($validateData);

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


    public function destroy(WebUser $webUser, House $house, HouseResident $houseResident): JsonResponse
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
