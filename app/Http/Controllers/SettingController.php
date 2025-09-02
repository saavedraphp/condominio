<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    public function showPage()
    {
        return view('admin.settings.index', [
            'isAdmin' => false,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $group = $request->query('group', 'general'); // Por defecto, el grupo 'general'

        $settings = Setting::where('group', $group)
            ->pluck('value', 'key'); // ¡Esta es la magia! Convierte la colección en un array asociativo.

        return response()->json($settings);
    }

    /**
     * Actualiza un conjunto de configuraciones.
     * El frontend enviará un objeto clave-valor.
     * Ejemplo de request:
     * { "site_title": "Nuevo Título", "tagline": "Nueva descripción" }
     */
    public function update(Request $request): JsonResponse
    {
        // Validamos que la data venga como un array de clave-valor
        $validatedData = $request->validate([
            'settings' => 'required|array',
            'group' => 'required|string',
        ]);

        $group = $validatedData['group'];
        $newSettings = $validatedData['settings'];
        $oldSettings = Setting::where('group', $group)->pluck('value', 'key');
        //$settings = $validatedData['settings'];
        try {
            DB::transaction(function () use ($newSettings, $group, $oldSettings) {
                foreach ($newSettings as $key => $newValue) {
                    $oldValue = $oldSettings->get($key);

                    // Lógica para eliminar el archivo antiguo
                    // Verificamos si el valor ha cambiado y si el valor antiguo era una ruta de archivo.
                    if ($oldValue !== $newValue && $this->isStorageFile($oldValue)) {
                        $this->deleteStorageFile($oldValue);
                    }

                    // Actualizamos o creamos la configuración en la base de datos
                    Setting::updateOrCreate(
                        ['key' => $key, 'group' => $group],
                        ['value' => $newValue ?? '']
                    );
                }
            });

            return response()->json(['message' => 'Configuraciones actualizadas correctamente.']);
        } catch (\Exception $e) {
            // Es una buena práctica registrar el error para poder depurarlo
            Log::error('Error al actualizar configuraciones: ' . $e->getMessage());

            // Devolvemos una respuesta de error al cliente
            return response()->json([
                'message' => 'Ocurrió un error al actualizar las configuraciones. Ningún cambio fue guardado.'
            ], 500); // Usamos el código de estado HTTP 500 (Error interno del servidor)
        }
    }

    private function isStorageFile($value): bool
    {
        // Un valor nulo o no string no puede ser una ruta de archivo.
        if (!$value || !is_string($value)) {
            return false;
        }

        // Verificamos si la cadena comienza con la URL base de nuestro storage.
        // En nuestro caso, los archivos se guardan con una URL como '/storage/settings/...'
        return Str::startsWith($value, '/storage/');
    }

    /**
     * Elimina un archivo del storage público a partir de su URL.
     *
     * @param string $url
     * @return void
     */
    private function deleteStorageFile(string $url): void
    {
        // Para obtener la ruta relativa al disco 'public', necesitamos quitar
        // el prefijo '/storage/' de la URL.
        // Ejemplo: '/storage/settings/logo.png' -> 'settings/logo.png'
        $path = Str::after($url, '/storage/');

        // Verificamos si el archivo existe en el disco 'public' y lo eliminamos.
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public function upload(Request $request): JsonResponse
    {
        // 1. Validar el archivo
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048', // Requerido, tipo imagen, mimes permitidos y tamaño máximo 2MB
        ]);

        // 2. Guardar el archivo en una carpeta específica (ej: 'settings') dentro del disco público
        // El método store() genera un nombre de archivo único automáticamente para evitar colisiones.
        $path = $request->file('file')->store('file_paths/settings', 'public');

        // 3. Devolver la URL pública del archivo
        // Storage::url($path) genera una URL como '/storage/file_paths/settings/archivo_unico.png'
        return response()->json([
            'url' => $path
        ]);
    }
}
