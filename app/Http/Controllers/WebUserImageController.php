<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\User;
use App\Models\WebUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WebUserImageController extends Controller
{
    public function index(WebUser $webUser):JsonResponse
    {
        $images = $webUser->images()->orderBy('date_document', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => '¡Excelente! Los documentos fueron encontrados exitosamente.',
            'data' => $images,
        ], JsonResponse::HTTP_OK);

    }
    public function store(Request $request, WebUser $webUser): JsonResponse
    {
        $request->validate([
            'file_path' => 'required|file|mimes:jpeg,png,jpg,pdf,doc,docx|max:2048',
            'title' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
            'date_document' => 'nullable|date',
            'is_visible' => 'nullable|boolean',
        ]);

        // 2. Manejar la subida del archivo

        try {
            if ($request->hasFile('file_path') && $request->file('file_path')->isValid()) {
                $file = $request->file('file_path');
                $nameOriginal = $file->getClientOriginalName();
                $filePath = $file->store('file_paths/profile/documents');

                if (!$filePath) {
                    return response()->json(['error' => 'No se pudo guardar el archivo.'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
                }
            } else {
                return response()->json(['error' => 'Archivo  inválido o no encontrado.'], JsonResponse::HTTP_BAD_REQUEST);
            }

            // 3. Crear el registro en la base de datos usando la relación
            $document = $webUser->images()->create([
                'title' => $request->title,
                'description' => $request->description,
                'file_path' => $filePath,
                'original_filename' => $nameOriginal,
                'date_document' => $request->date_document ?? now(),
                'order' => $request->orden ?? 0,
                'is_visible' => $request->is_visible ?? true,
            ]);

            // 4. Retornar una respuesta JSON con un mensaje de éxito
            return response()->json([
                'success' => true,
                'message' => '¡Excelente! El documento fue registrado exitosamente.',
                'data' => $document,
            ], JsonResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            // Loguear el error para depuración
            Log::error('Error al subir el archivo: ' . $e->getMessage());
            return response()->json(['error' => 'Error al subir el archivo.'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, Image $image): JsonResponse
    {
        $validatedData = $request->validate([
            // 'sometimes' significa: "valida esto SOLO si está presente en la request"
            'file_path' => 'sometimes|required|file|mimes:jpeg,png,jpg,pdf,doc,docx|max:2048',
            'title' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
            'date_document' => 'nullable|date',
            'is_visible' => 'nullable|boolean',
        ]);

        // 2. Manejar la subida del archivo
        try {
            // 2. Manejar la subida del archivo (si existe)
            if ($request->hasFile('file_path')) {
                // Eliminar el archivo antiguo para no dejar basura en el servidor
                if ($image->file_path) {
                    Storage::delete($image->file_path);
                }

                // Guardar el nuevo archivo
                $file = $request->file('file_path');
                $filePath = $file->store('file_paths/profile/documents');

                // Añadir los datos del nuevo archivo a los datos validados
                $validatedData['file_path'] = $filePath;
                $validatedData['original_filename'] = $file->getClientOriginalName();
            }

            $image->update($validatedData);

            // 4. Retornar una respuesta JSON
            return response()->json([
                'success' => true,
                'message' => '¡Excelente! El documento fue actualizado exitosamente.',
                'data' => $image, // Devolvemos el modelo actualizado
            ], JsonResponse::HTTP_OK); // HTTP_OK (200) es más apropiado para un update exitoso

        } catch (\Exception $e) {
            // Loguear el error para depuración
            Log::error('Error al subir el archivo: ' . $e->getMessage());
            return response()->json(['error' => 'Error al subir el archivo.'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function previewImage(Image $image): JsonResponse
    {
        $url =  Storage::url($image->file_path);
        $mimeType = Storage::mimeType($image->file_path);

        return response()->json([
            'success' => true,
            'message' => '¡Excelente! El documento fue encontrado exitosamente.',
            'data' => [
                'title' => $image->title,
                'url' => $url,
                'original_filename' => $image->original_filename,
                'mime_type' => $mimeType,
            ],
        ]);

    }

    public function destroy(WebUser $webUser, Image $image): JsonResponse
    {
        $filePath = $image->file_path;
        try {

            DB::transaction(function () use ($image, $filePath) {
                if ($filePath && Storage::exists($filePath)) {
                    Storage::delete($filePath);
                }
                $image->delete();

            });

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! El Archivo se elimino correctamente.',
                JsonResponse::HTTP_OK
            ]);

        } catch (\exception $e) {
            $errorMessage = 'Error eliminando el registro id: ' . $image?->id;
            Log::error($errorMessage . ': ' . $e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al intentar eliminar el registro.'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
