<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SecuriryRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SecurityController extends Controller
{
    public function showPage(): View
    {
        $routes = [
            'base' => route('admin.securities.index'),
        ];

        return view('admin.securities.index', compact('routes'));
    }

    public function index(): JsonResponse
    {
        try {
            $users = User::role('security')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($users);
        } catch (\Exception $e) {
            Log::error('Error al intentar obtener los datos' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Ócurrio un error al intentar obtener los datos'], 500);
        }
    }

    public function store(SecuriryRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        try {
            $dataToCreate = array_merge(
                $validatedData,
                [
                    'password' => bcrypt('123456'),
                ]);

            $user = User::create($dataToCreate);

            $user->assignRole('security');
            $token = Str::random(64);

            $user->activationToken()->create(['token' => $token]);
            $activationUrl = url('/activar-cuenta/' . $token);
            //Mail::to($webUser->email)->send(new AccountActivationMail($activationUrl));

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! La operación sealizó con éxito.',
                'data' => $user,
            ], 201);

        } catch (\exception $e) {
            Log::error('Error al adicionar el personal de seguridad' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Ócurrio un error al intentar insertar el registro : ' . $e->getMessage()], 500);
        }
    }

    public function update(SecuriryRequest $request, User $security): JsonResponse
    {
        $validatedData = $request->validated();
        try {
            $security->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! La operación se realizó con éxito.',
                'data' => $security,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al actualizar el personal de seguridad: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Ócurrio un error al intentar actualizar el el registro'], 500);
        }
    }

    public function destroy(User $security): JsonResponse
    {
        try {
            $security->delete();

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! La operación se realizó con éxito.',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al eliminar el personal de seguridad: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Ócurrio un error al intentar eliminar el registro'], 500);
        }
    }
}
