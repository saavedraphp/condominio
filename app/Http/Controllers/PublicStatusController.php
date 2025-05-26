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
        $resultDebt = $this->checkUserDebtStatus($user->id); // Reutiliza tu lógica de deuda

        return view('user.qr_verification.result', [
            'user'      => $user,
            'debt' => $resultDebt['debtAmount'],
            'status' => true,

        ]);
    }

    private function checkUserDebtStatus(int $userId): array
    {
        $result = [
            'debtAmount' => 100,
        ];
        return $result;
    }
}
