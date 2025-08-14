<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class VisitPassReportController extends Controller
{
    public function showListPage(): View
    {
        $routes = [
            'base' => route('admin.reports.visit-passes.index'),
            'securities' => route('admin.securities.index'),
        ];
        return view('admin.reports.visit_pass.index', [
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

        $query = AccessLog::with(['user','visitPass.house','visitPass.creator'])
            ->when($request->filled('start_date'), function ($query) use ($validated) {
                $query->whereDate('created_at', '>=', $validated['start_date']);
            })
            ->when($request->filled('end_date'), function ($query) use ($validated) {
                $query->whereDate('created_at', '<=', $validated['end_date']);
            })
        ->when($request->filled('security_id'), function ($query) use ($request) {
                $query->where('user_id', $request->input('security_id'));
            });
        $totalItem = $query->count();

        $data = $query->orderBy('created_at','desc')->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'creator' => [
                      'name' => $log->visitPass && $log->visitPass->creator ? $log->visitPass->creator->name : 'Desconocido',
                    ],
                    'security' => [
                        'name' => $log->user ? $log->user->name : 'Desconocido',
                    ],
                    'pass' => [
                        'title' => $log->visitPass ? $log->visitPass->title : 'No especificada',
                        'starts_at' => $log->visitPass ? $log->visitPass->starts_at->format('Y-m-d') : 'No especificada',
                        'expires_at' => $log->visitPass ? $log->visitPass->expires_at->format('Y-m-d') : 'No especificada',
                    ],
                    'property' => [
                        'address' => $log->visitPass->house->address ?? 'No especificada',
                    ],
                    'visit_pass_id' => $log->visit_pass_id,
                    'code_entered' => $log->code_entered,
                    'event_type' => $log->event_type,
                    'status' => $log->status,
                    'remarks' => $log->remarks,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return [
            'items' => $data,
            'total_items' => $totalItem,
        ];
    }
}
