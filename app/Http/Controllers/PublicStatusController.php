<?php

namespace App\Http\Controllers;

use App\Http\Controllers\User\ProfileController;
use App\Models\WebUser;
use App\Services\UserDebtService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PublicStatusController extends Controller
{
    public function showStatusByToken(Request $request, string $token, UserDebtService $debtService): View
    {
        // Busca al usuario por su token de acceso público
        $user = WebUser::where('public_access_token', $token)->first();

        if (!$user) {
            abort(404, 'Token de verificación inválido o expirado.');
        }

        // --- Lógica para verificar la deuda (SIMPLIFICADA) ---
        $resultDebt = $this->checkUserDebtStatus($user->id, $debtService); // Reutiliza tu lógica de deuda

        return view('user.qr_verification.result', [
            'user'      => $user,
            'debt' => $resultDebt['debtAmount'],
            'status' => true,

        ]);
    }

    public function checkUserDebtStatus(int $userId, UserDebtService $debtService): array // <-- Inyecta el servicio
    {
        $user = WebUser::findOrFail($userId);

        // Delega todo el cálculo al servicio
        $totalDebt = $debtService->calculateTotalDebt($user);

        $result = [
            'debtAmount' => $totalDebt,
        ];

        return $result;
    }

}
