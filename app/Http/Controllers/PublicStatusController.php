<?php

namespace App\Http\Controllers;

use App\Models\WebUser;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicStatusController extends Controller
{
    public function showStatusByToken(Request $request, string $token): View
    {
        // Busca al usuario por su token de acceso público
        $user = WebUser::where('public_access_token', $token)->first();

        if (!$user) {
            abort(404, 'Token de verificación inválido o expirado.');
        }

        // --- Lógica para verificar la deuda (SIMPLIFICADA) ---
        $hasDebt = $this->checkUserDebtStatus($user->id); // Reutiliza tu lógica de deuda

        $statusMessage = $hasDebt
            ? 'Presenta pagos pendientes con el condominio.'
            : 'Se encuentra al día con sus pagos al condominio.';
        $statusColor = $hasDebt ? 'red' : 'green';

        return view('qr_verification.result', [
            'userName'      => $user->name,
            'statusMessage' => $statusMessage,
            'statusColor'   => $statusColor,
        ]);
    }

    private function checkUserDebtStatus(int $userId): bool
    {
        // Tu lógica real aquí...
        return false; // Placeholder
    }
}
