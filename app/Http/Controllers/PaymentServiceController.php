<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\PaymentService;
use App\Models\WebUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PaymentServiceController extends Controller
{
    public function showPage(House $house): View
    {
        $webUser = Auth::guard('web_user')->user();
        return view('user.houses.electricity_consumption', [
                'webUserId' => $webUser->id,
                'houseId' => $house->id,
                'typeServiceId' => 1, //LUZ
            ]
        );
    }

    public function showPageWater(House $house): View
    {
        $webUser = Auth::guard('web_user')->user();
        return view('user.houses.water_consumption', [
                'webUserId' => $webUser->id,
                'houseId' => $house->id,
                'typeServiceId' => 0, //
            ]
        );
    }

    public function index(Request $request, House $house): JsonResponse
    {
        $typeService = $request->get('type_service');
        try {
            $userId = Auth::guard('web_user')->id();
            $servicePayments = PaymentService::query()
                ->where('house_id', $house->id)
                ->where('web_user_id', $userId)
                ->where('service_id', $typeService)
                ->get();
            return response()->json($servicePayments);
        } catch (\Exception $e) {
            $errorMessage = 'Ocurrio un error al intentar obtener la los datos. ';
            Log::error($errorMessage . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $errorMessage . $e->getMessage()
            ], 500);
        }
    }

}
