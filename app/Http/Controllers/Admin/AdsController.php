<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdRequest;
use App\Models\Ad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdsController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $ads = Ad::query()
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

    public function store(AdRequest $request): JsonResponse
    {
        try {
            $validateData = $request->validated();
            $filePath = null;
            if ($request->hasFile('file_path') && $request->file('file_path')->isValid()) {
                $file = $request->file('file_path');
                $filePath = $file->store('file_paths/ads', 'public');

                if (!$filePath) {
                    return response()->json(['error' => 'No se pudo guardar el archivo.'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
                }
            } else {
                return response()->json(['error' => 'Archivo  inválido o no encontrado.'], JsonResponse::HTTP_BAD_REQUEST);
            }

            $validateData['file_path'] = $filePath;
            $ad = Ad::create($validateData);

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! se agregado un Anuncio correctamente.',
                'data' => $ad,
            ], JsonResponse::HTTP_CREATED);

        } catch (\exception $e) {
            Log::error('Error al adicionar un Anuncio' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Ócurrio un error al intentar agregar un Anuncio: '
            ], 500);
        }
    }

    public function update(AdRequest $request, Ad $ad): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $dataToUpdate = [
                'title' => $validatedData['title'],
                'description' => $validatedData['description'],
                'start_day' => $validatedData['start_day'],
                'end_day' => $validatedData['end_day'],
                'active' => $validatedData['active'],
            ];

            if ($request->hasFile('file_path') && $request->file('file_path')->isValid()) {
                if ($ad->file_path && Storage::disk('public')->exists($ad->file_path)) {
                    Storage::disk('public')->delete($ad->file_path);
                }

                $file = $request->file('file_path');
                $filePath = $file->store('file_paths/ads', 'public');

                $dataToUpdate['file_path'] = $filePath;
            }
            $updateSuccessful = $ad->update($dataToUpdate);

            if ($updateSuccessful) {
                return response()->json([
                    'success' => true,
                    'message' => '¡Excelente! se edito el registro correctamente.',
                    'data' => $ad,
                ], JsonResponse::HTTP_OK);
            } else {
                Log::warning('Fallo al actualizar el registro.', ['id' => $ad->id]);
                return response()->json(['success' => false, 'message' => 'No se pudo actualizar el registro.'], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error actualizando el registro ID ' . $ad->id . ': ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al intentar actualizar el registro.',
            ], 500);
        }

    }

    public function destroy(Ad $ad): JsonResponse
    {
        $filePath = $ad->file_path;
        try {
            DB::transaction(function () use ($ad, $filePath) {
                $disk = 'public';
                if ($filePath && Storage::disk($disk)->exists($filePath)) {
                    Storage::disk($disk)->delete($filePath);
                }
                $ad->delete();
            });


            return response()->json([
                'success' => true,
                'message' => '¡Excelente! el registro se ha eliminado correctamente.',
                JsonResponse::HTTP_OK
            ]);

        } catch (\Exception $e) {
            Log::error('Error eliminando el registro ID ' . $ad->id . ': ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Error al intentar eliminar el registro.'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
