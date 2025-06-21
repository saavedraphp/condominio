<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuotationRequest;
use App\Models\Project;
use App\Models\Quotation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProjectQuotationController extends Controller
{
    public function store(QuotationRequest $request, Project $project): JsonResponse
    {
        try {
            $filePath = null;
            if ($request->hasFile('file') && $request->file('file')->isValid()) {
                $file = $request->file('file');
                $filePath = $file->store('file_paths/quotations', 'public');
            }

            $quotation = $project->quotations()->create([
                'company_name' => $request->input('company_name'),
                'amount' => $request->input('amount'),
                'file_path' => $filePath,
            ]);

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! La cotización fue registrada exitosamente.',
                'quotation' => $quotation
            ], JsonResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Error adding quotation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al adicionar la cotización.'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(QuotationRequest $request, Project $project, Quotation $quotation): JsonResponse
    {
        // Asegurarse que la cotización pertenece al proyecto (Route Model Binding ya lo hace si está bien configurado)
        if ($quotation->project_id !== $project->id) {
            return response()->json(['success' => false, 'message' => 'La cotización no pertenece a este proyecto.'], 403);
        }

        try {
            $dataToUpdate = $request->only(['company_name', 'amount']);

            if ($request->hasFile('file')) {
                // Eliminar archivo antiguo si existe
                if ($quotation->file_path && Storage::disk('public')->exists($quotation->file_path)) {
                    Storage::disk('public')->delete($quotation->file_path);
                }
                // Subir nuevo archivo
                $file = $request->file('file');
                //$dataToUpdate['file_path'] = $file->storeAs('quotations/' . $project->id, $fileName, 'public');
                $dataToUpdate['file_path'] = $file->store('file_paths/quotations', 'public');
            }

            $quotation->update($dataToUpdate);

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! La cotización fue actualizada exitosamente.',
                'quotation' => $quotation,
                ], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Error updating quotation: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Ocurrio un error al intentar actualizar la cotizacion.'
            ], 500);
        }
    }

    public function destroy(Project $project, Quotation $quotation): JsonResponse
    {
        $disk = 'public';
        $filePath = $quotation->file_path ?? null;
        if ($quotation->project_id !== $project->id) {
            return response()->json(['success' => false, 'message' => 'La cotización no pertenece a este proyecto.'], 403);
        }

        try {
            // Si la cotización eliminada era la elegida, desvincularla del proyecto
            if ($project->chosen_quotation_id === $quotation->id) {
                $project->chosen_quotation_id = null;
                $project->save();
            }

            if ($filePath && Storage::disk($disk)->exists($filePath)) {
                Storage::disk($disk)->delete($filePath);
            }
            $quotation->delete();

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! Cotización eliminado correctamente.'
            ], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Error deleting quotation: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Ocurrió un error al intentar eliminar la Cotización.'], 500);
        }
    }
}
