<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdRequest;
use App\Models\Ad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdsController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $ads = Ad::query()
                ->select(['id', 'title', 'description', 'status', 'start_day', 'end_day', 'created_at'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($ads);

        } catch (\Exception $e) {
            Log::error('Error al intentar obtener los datos' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Ócurrio un error al intentar obtener los datos: ' . $e->getMessage()], 500);
        }

    }

    public function showListPage(): View
    {
        return view('admin.ads');
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validateData = $request->validate([
                'title' => 'required|string:max:50',
                'description' => 'required|string:max:250',
                'start_day' => 'required|date',
                'end_day' => 'required|date',
                'status' => 'required|string|max:10',
            ]);

            $vehicle = Ad::create($validateData);
            return response()->json([
                'success' => true,
                'message' => '¡Excelente! se agregado un Anuncio correctamente.',
                'data' => $vehicle,
            ],201);
        } catch (\exception $e) {
            Log::error('Error al adicionar un Anuncio' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Ócurrio un error al intentar agregar un Anuncio: ' . $e->getMessage()], 500);
        }

    }

    public function update(AdRequest $request, Ad $ad): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            $updateSuccessful = $ad->update($validatedData);

            if ($updateSuccessful) {
                return response()->json([
                    'success' => true,
                    'message' => '¡Excelente! se edito el registro correctamente.',
                    'data' => $ad,
                ], 201);
            } else {
                Log::warning('Fallo al actualizar el registro.', ['id' => $ad->id]);
                return response()->json(['success' => false, 'message' => 'No se pudo actualizar el registro.'], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error actualizando el registro ID ' . $ad->id . ': ' . $e->getMessage());

            return response()->json(['success' => false,
                'message' => $e->getMessage(),
                'data' => $e->getMessage()], 500);
        }

    }

    public function destroy(Ad $ad): JsonResponse
    {
        try {
            $ad->delete();

            return response()->json(['success' => true, 'message' => '¡Excelente! el registro se ha eliminado correctamente.']);

        } catch (\Exception $e) {
            Log::error('Error eliminando el registro ID ' . $ad->id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al intentar eliminar el registro.'], 500);
        }
    }
}
