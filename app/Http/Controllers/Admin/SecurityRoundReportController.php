<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SecurityRoundReportController extends Controller
{
    public function showListPage(): View
    {
        $routes = [
            'base' => route('admin.reports.security-round.index'),
            'securities' => route('admin.securities.index'),
        ];
        return view('admin.reports.security_round.index', [
            'routes' => $routes,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $data = $this->getData($request);
            return response()->json([
                'success' => true,
                'data' => $data['items'],
                'totals' => $data['total_items'],
            ], JsonResponse::HTTP_OK);

        } catch (\Exception $e) {
            Log::error('Error al obtener el reporte de pases: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al procesar la solicitud: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function getData(Request $request): array
    {
        // 1. Validar las fechas de entrada
        $validated = $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $startOfDay = Carbon::parse($validated['start_date'])->startOfDay();
        $endOfDay = Carbon::parse($validated['end_date'])->endOfDay();

        $query = ActivityLog::with(['user','qrCode'])
            ->when($request->filled('start_date'), function ($query) use ($startOfDay, $validated) {
                $query->where('created_at', '>=', $startOfDay);
            })
            ->when($request->filled('end_date'), function ($query) use ($endOfDay, $validated) {
                $query->where('created_at', '<=', $endOfDay);
            })
            ->when($request->filled('security_id'), function ($query) use ($request) {
                $query->where('user_id', $request->input('security_id'));
            });
        $totalItem = $query->count();

        $data = $query->orderBy('created_at','desc')->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'user' => [
                        'name' => $log->user ? $log->user->name : 'Desconocido',
                    ],
                    'qr_code' => [
                        'title' => $log->qrCode ? $log->qrCode->title : 'No especificada',
                    ],
                    'remarks' => $log->remarks,
                    'created_at' => $log->created_at,
                ];
            });

        return [
            'items' => $data,
            'total_items' => $totalItem,
        ];
    }

}
