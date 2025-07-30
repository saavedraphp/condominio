<?php

namespace App\Services;

class SharedViewDataService
{
    /**
     * Obtiene los datos compartidos para ser usados en vistas o PDFs.
     *
     * @param bool $isPreview Determina si la ruta del logo es para una vista web (asset) o para el sistema de archivos (storage_path).
     * @return array
     */
    public function get(bool $isPreview = false): array
    {
        // La lógica original, ahora en un método público.
        $logoPath = $isPreview
            ? asset('assets/images/logo.jpg')
            : storage_path('app/public/file_paths/profile/nVcxTYTvFIndE6SVndfDMUTG6uFp5CPcCSFKhmFc.jpg');

        // A medida que esto crezca, puedes agregar más lógica aquí.
        // Por ejemplo, obtener datos del usuario autenticado, configuraciones globales, etc.

        return [
            'logo_path' => $logoPath,
            'date' => now()->format('d/m/Y'),
            // 'company_name' => config('app.name'),
            // 'current_user' => auth()->user() ? auth()->user()->name : 'Invitado',
        ];
    }
}
