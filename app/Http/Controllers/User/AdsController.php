<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdsController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $ads = Ad::query()
                ->select(['id', 'title', 'description', 'active', 'start_day', 'end_day', 'created_at'])
                ->where('active', true)
                ->orderBy('start_day', 'desc')
                ->limit(5)
                ->get();

            return response()->json($ads);
        } catch (\Exception $e) {
            Log::error('Error al intentar obtener los datos' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Ócurrio un error al intentar obtener los datos: ' . $e->getMessage()], 500);
        }

    }
}
