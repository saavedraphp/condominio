<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function showPage(): View
    {
        $routes = [
            'base' => route('user.projects.index'),
            'preview_quotation' => route('user.quotation.preview-image',[
                'quotation' => 'PLACEHOLDER_1'
            ]),
        ];

        $data = [
            'routes' => $routes,
            'isAdmin' => false
        ];

        return view('user.projects.index', $data);
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $projects = Project::with(['quotations', 'chosenQuotation'])
                ->orderBy('start_date', 'desc')
                ->get();

            return response()->json(['data' => $projects]);

        } catch (\Exception $e) {
            Log::error('Error al obtener los datos ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al intentar obtener los tipos de presupuesto: ' . $e->getMessage()
            ], 500);
        }

    }
}
