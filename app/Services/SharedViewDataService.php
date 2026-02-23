<?php

namespace App\Services;

class SharedViewDataService
{
    /**
     * Obtiene los datos compartidos para ser usados en vistas o PDFs.
     *
     * @param bool $isPreview Determina si la ruta del logo es para una vista web (asset) o para el sistema de archivos (storage_path).
     * @return string
     */
    public function get(string $imagePath, bool $isPreview = false): string
    {
        // La lógica original, ahora en un método público.
        $logoPath = $isPreview
            ? asset('storage/'.$imagePath)
            : storage_path('app/public/'.$imagePath);

        // A medida que esto crezca, puedes agregar más lógica aquí.
        // Por ejemplo, obtener datos del usuario autenticado, configuraciones globales, etc.

        return  $logoPath;
    }
}
