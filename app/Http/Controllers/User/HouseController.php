<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\House;
use App\Models\WebUser;
use App\Traits\ManagesHouseSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class HouseController extends Controller
{
    use ManagesHouseSession;
    public function showPage(): View
    {
        $this->clearHouseSession();
        $webUserId = Auth::guard('web_user')->user();
        return view('user.houses.houses', ['webUserId' => $webUserId]);
    }

    public function dashboard(House $house): View
    {
        Session::put('selected_house_id', $house->id);
        Session::put('selected_house_name', $house->property_unit);

        $webUserId = Auth::guard('web_user')->id();
        return view('user.houses.dashboard', ['webUserId' => $webUserId, 'houseId' => $house->id]);
    }

    public function show(House $house): JsonResponse
    {
        $user = Auth::guard('web_user')->user();
        $isRelated = $house->webUsers()->where('web_user_id', $user->id)->exists();

        if (!$user || !$isRelated) {
            return response()->json(['message' => 'No autorizado para acceder a este recurso o no encontrado.'], JsonResponse::HTTP_FORBIDDEN);
        }

        return response()->json($house, JsonResponse::HTTP_OK);
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

    public function index(): JsonResponse
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
