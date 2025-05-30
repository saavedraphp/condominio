<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProyectRequest;
use App\Models\Project;
use App\Models\Quotation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function showListPage(): View
    {
        return view('admin.projects_quotations.index');
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $projects = Project::with(['quotations', 'chosenQuotation'])
                ->orderBy('created_at', 'desc')
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

    public function store(ProyectRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $project = Project::create([
                'name' => $request->input('name'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'additional_expenses' => $request->input('additional_expenses', 0),
                'details' => $request->input('details'),
            ]);

            $createdQuotations = [];
            if ($request->has('quotations')) {
                foreach ($request->input('quotations') as $index => $quotationData) {
                    $filePath = null;
                    if ($request->hasFile("quotations.{$index}.file")) {
                        // El path en el frontend es `quotations[${index}][file]`
                        // Laravel lo interpreta como `quotations.*.file` en la validación
                        // y $request->file("quotations.{$index}.file") para accederlo.
                        $file = $request->file("quotations.{$index}.file");
                        $nameOriginal = $file->getClientOriginalName();
                        $filePath = $file->store('file_paths/quotations', 'public');
                    }

                    $createdQuotations[] = $project->quotations()->create([
                        'company_name' => $quotationData['company_name'],
                        'amount' => $quotationData['amount'],
                        'file_path' => $filePath,
                    ]);
                }
            }

            // Opcional: si se marcó una cotización como elegida durante la creación
            // Esto es más complejo si la cotización elegida es una de las nuevas.
            // Sería más simple establecerla después de que el proyecto y las cotizaciones existan.
            // Si `chosen_quotation_index` se envía desde el frontend:
            if ($request->has('chosen_quotation_index') && isset($createdQuotations[$request->input('chosen_quotation_index')])) {
                $project->chosen_quotation_id = $createdQuotations[$request->input('chosen_quotation_index')]->id;
                $project->save();
            }


            DB::commit();
            // Recargar relaciones para la respuesta
            $project->load(['quotations', 'chosenQuotation']);
            return response()->json([
                'success' => true,
                'message' => 'Project created successfully.',
                'project' => $project
            ], JsonResponse::HTTP_CREATED);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating project: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create project: ' . $e->getMessage()
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(ProyectRequest $request, Project $project): JsonResponse
    {
        try {
            $project->update($request->only([
                'name', 'start_date', 'end_date', 'additional_expenses', 'details'
            ]));

            $project->load(['quotations', 'chosenQuotation']);
            return response()->json(['success' => true, 'message' => 'Proyecto actualizado con éxito.', 'project' => $project]);

        } catch (\Exception $e) {
            Log::error('Error Actualizando el proyecto: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error Actualizando el proyecto.'], 500);
        }
    }

    public function destroy(Project $project): JsonResponse
    {
        DB::beginTransaction();
        try {
            foreach ($project->quotations as $quotation) {
                if ($quotation->file_path && Storage::disk('public')->exists($quotation->file_path)) {
                    Storage::disk('public')->delete($quotation->file_path);
                }
            }
            // Las cotizaciones se eliminarán en cascada si la FK está configurada con onDelete('cascade')
            $project->delete();
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Proyecto eliminado con éxito.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting project: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al eliminar el proyecto.'], 500);
        }
    }

    public function setChosenQuotation(Request $request, Project $project): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'chosen_quotation_id' => ['nullable', 'integer', function ($attribute, $value, $fail) use ($project) {
                if ($value && !$project->quotations()->where('id', $value)->exists()) {
                    $fail('La cotización seleccionada no pertenece a este proyecto..');
                }
            }],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Errores de validación', 'errors' => $validator->errors()], 422);
        }

        try {
            $project->chosen_quotation_id = $request->input('chosen_quotation_id');
            $project->save();
            $project->load(['quotations', 'chosenQuotation']);
            return response()->json(['success' => true, 'message' => 'Cotización seleccionada.', 'project' => $project]);
        } catch (\Exception $e) {
            Log::error('Error setting chosen quotation: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to set chosen quotation.'], 500);
        }
    }


}
