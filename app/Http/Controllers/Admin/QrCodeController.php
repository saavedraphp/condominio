<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QrCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class QrCodeController extends Controller
{
    public function showListPage(): View
    {
        $routes = [
            'base' => route('admin.qr-codes.index'),
        ];
        return view('admin.qr_codes.index', [
            'routes' => $routes,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $data = QrCode::all();
            return response()->json([
                'success' => true,
                'data' => $data,
            ], JsonResponse::HTTP_OK);

        } catch (\Exception $e) {
            Log::error('Error al obtener los codigos QR ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al procesar la solicitud',
            ], 500);
        }
    }
}
