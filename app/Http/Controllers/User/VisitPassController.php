<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\House;
use App\Models\VisitPass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class VisitPassController extends Controller
{
    public function showPage(House $house): View
    {
        $webUser = Auth::guard('web_user')->user();
        $routes = [
            'base' => route('user.house.visit-passes.index', ['house' => $house->id]),
        ];
        $data = [
            'webUser' => $webUser,
            'house' => $house,
            'routes' => $routes,
        ];
        return view('user.visit_passes.index', $data);
    }

    public function index(House $house): JsonResponse
    {
        try {

            $data = Auth::guard('web_user')->user()->visitPasses()
                ->with('house', 'members')
                ->latest()->get();

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('Error al intentar obtener los datos' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Ócurrio un error al intentar obtener los datos'], 500);
        }
    }
}
