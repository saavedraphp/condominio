<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentRequest;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function showPage(): View
    {
        return view('user.documents', [
            'urlBase' => route('user.documents.index'),
            'isAdmin' => false,
        ]);
    }

    public function showListPageAdmin(): View
    {
        return view('user.documents', [
            'urlBase' => route('admin.documents.index'),
            'isAdmin' => true,
        ]);
    }


    public function index(Request $request)
    {

        $documents = Document::query()
            ->where('active', true)
            ->orderBy('created_at', 'desc')->get();


        return response()->json($documents);
    }

    /**
     * Muestra los detalles de un documento específico.
     */
    public function show(Document $document)
    {
        // Añadimos la URL de descarga segura y el tamaño legible
        $document->download_url = route('documents.download', ['document' => $document->id]);
        // Excluimos file_path para no exponerlo directamente
        return response()->json($document);
    }

    /**
     * Permite la descarga segura del archivo.
     */
    public function download(Document $document): StreamedResponse|\Illuminate\Http\JsonResponse
    {
        // Verifica que el archivo exista en el storage
        if (!Storage::disk('public')->exists($document->file_path)) {
            // Loggea el error para depuración
            Log::error("Archivo no encontrado en storage: ID {$document->id}, Path: {$document->file_path}");
            return response()->json(['message' => 'File not found.'], 404);
        }

        // Retorna la descarga usando el nombre original del archivo
        return Storage::disk('public')->download($document->file_path, $document->original_filename);
    }


    public function store(DocumentRequest $request): JsonResponse
    {
        $userId = Auth::guard('web_user')->id();
        try {
            $validatedData = $request->validated();

            // --- 2. Manejo del Archivo ---
            $filePath = null;
            if ($request->hasFile('file_path') && $request->file('file_path')->isValid()) {
                $file = $request->file('file_path');
                $nameOriginal = $file->getClientOriginalName();
                $sizeInBytes = $file->getSize();
                $extension = $file->getExtension();

                $filePath = $file->store('file_paths/documents', 'public');

                if (!$filePath) {
                    return response()->json(['error' => 'No se pudo guardar el archivo.'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
                }
            } else {
                return response()->json(['error' => 'Archivo  inválido o no encontrado.'], JsonResponse::HTTP_BAD_REQUEST);
            }


            $document = Document::create([
                'title' => $validatedData['title'],
                'description' => $validatedData['description'],
                'file_path' => $filePath,
                'original_filename' => $nameOriginal,
                'extension' => $extension,
                'size' => $sizeInBytes,
                'active' => true,
                'user_id' => $userId,
            ]);

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! El documento fue registrado exitosamente.',
                'data' => $document,
            ], JsonResponse::HTTP_CREATED);


        } catch (\exception $e) {
            $messageError = 'Ócurrio un error al intentar subir el documento. ';
            Log::error($messageError . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $messageError],
                500);
        }
    }

    public function update(DocumentRequest $request, Document $document): JsonResponse
    {
        $validatedData = $request->validated();
        $userId = Auth::guard('web_user')->id();
        try {
            $dataToUpdate = [
                'title' => $validatedData['title'],
                'description' => $validatedData['description'],
                'user_id' => $userId
            ];

            if ($request->hasFile('file_path') && $request->file('file_path')->isValid()) {

                if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                    Storage::disk('public')->delete($document->file_path);
                }

                $file = $request->file('file_path');
                $filePath = $file->store('file_paths/documents', 'public');

                $dataToUpdate['file_path'] = $filePath;
                $dataToUpdate['original_filename'] = $file->getClientOriginalName();
                $dataToUpdate['extension'] = $file->getClientOriginalExtension();
                $dataToUpdate['size'] = $file->getSize();
            }

            $document->update($dataToUpdate);

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! El documento fue actualizado exitosamente.',
                'data' => $document,
            ], JsonResponse::HTTP_OK);

        } catch (\exception $e) {
            $errorMessage = 'Error al actualizar el documento id: ' . $document?->id;
            Log::error($errorMessage . ': ' . $e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al intentar actualizar el documento.'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function destroy(Document $document): JsonResponse
    {
        $filePath = $document->file_path;

        try {

            DB::transaction(function () use ($document, $filePath) {
                $disk = 'public';
                if ($filePath && Storage::disk($disk)->exists($filePath)) {
                    Storage::disk($disk)->delete($filePath);
                }
                $document->delete();

            });

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! Documento eliminado correctamente.',
                JsonResponse::HTTP_OK
            ]);

        } catch (\exception $e) {
            $errorMessage = 'Error eliminando el registro id: ' . $document?->id;
            Log::error($errorMessage . ': ' . $e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al intentar eliminar el documento.'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
