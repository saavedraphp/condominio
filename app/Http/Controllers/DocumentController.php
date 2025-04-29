<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DocumentController extends Controller
{
    public function showPage(): View
    {
        return view('user.documents');
    }

    public function index(Request $request)
    {

        $documents = Document::query()
            ->where('is_visible', true)
            ->select('id', 'title', 'type', 'created_at') // Campos para la lista
            ->orderBy('created_at', 'desc')->get();


        return response()->json($documents);
    }

    /**
     * Muestra los detalles de un documento específico.
     */
    public function show(Document $document)
    {
        // Verifica si el usuario puede ver este documento específico
        if (!$document->is_visible_to_residents) {
            // Opcional: Podrías añadir lógica más compleja aquí si la visibilidad
            // depende del tipo de usuario, etc.
            return response()->json(['message' => 'Document not found or not accessible.'], 404);
        }

        // Añadimos la URL de descarga segura y el tamaño legible
        $document->append(['download_url', 'readable_size']);

        // Excluimos file_path para no exponerlo directamente
        return response()->json($document->makeHidden('file_path'));
    }

    /**
     * Permite la descarga segura del archivo.
     */
    public function download(Document $document): StreamedResponse|\Illuminate\Http\JsonResponse
    {
        // Verifica si el usuario puede descargar este documento
        if (!$document->is_visible_to_residents) {
            return response()->json(['message' => 'Document not found or not accessible.'], 404);
        }

        // Verifica que el archivo exista en el storage
        if (!Storage::disk('public')->exists($document->file_path)) {
            // Loggea el error para depuración
            \Log::error("Archivo no encontrado en storage: ID {$document->id}, Path: {$document->file_path}");
            return response()->json(['message' => 'File not found.'], 404);
        }

        // Retorna la descarga usando el nombre original del archivo
        return Storage::disk('public')->download($document->file_path, $document->original_filename);
    }

    // --- Métodos Adicionales (Para Administradores) ---
    // Aquí irían los métodos store, update, destroy para gestionar documentos
    // Estos requerirían autorización específica para administradores.
}
