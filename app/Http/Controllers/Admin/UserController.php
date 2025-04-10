<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Mail\AccountActivationMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    public function showListPage(): View
    {
        return view('admin.users');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $users = User::query()
                ->select(['id', 'name', 'email', 'phone', 'email_verified_at', 'status'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($users);
        } catch (\Exception $e) {
            Log::error('Error al intentar obtener los datos' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Ócurrio un error al intentar obtener los datos: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $dataToCreate = array_merge(
                $validatedData,
                [
                    'password' => bcrypt(Str::random(10)),
                    'status' => 'inactive'
                ]);

            $user = User::create($dataToCreate);

            $token = Str::random(64);

            DB::table('account_activations')->insert([
                'email' => $user->email,
                'token' => $token,
                'created_at' => Carbon::now()
            ]);

            $activationUrl = url('/activar-cuenta/' . $token);

            Mail::to($user->email)->send(new AccountActivationMail($activationUrl));


            return response()->json([
                'success' => true,
                'message' => '¡Excelente! Usuario registrado. A la espera de que confirme su cuenta para la activación.',
                'data' => $user,
            ], 201);
        } catch (\exception $e) {
            Log::error('Error al adicionar un usuario' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Ócurrio un error al intentar insertar un usuario : ' . $e->getMessage()], 500);
        }
    }

    public function update(UserRequest $request, User $user): JsonResponse
    {
        try {
            $updateSuccessful = $user->update($request->only(['name', 'phone', 'status']));

            if ($updateSuccessful) {
                return response()->json([
                    'success' => true,
                    'message' => '¡Excelente! se edito el registro correctamente.',
                    'data' => $user,
                ], 201);
            } else {
                Log::warning('Fallo al actualizar el registro.', ['id' => $user->id]);
                return response()->json(['success' => false, 'message' => 'No se pudo actualizar el registro.'], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error actualizando el registro ID ' . $user->id . ': ' . $e->getMessage());

            return response()->json(['success' => false,
                'message' => $e->getMessage(),
                'data' => $e->getMessage()], 500);
        }
    }


    public function destroy(User $user): JsonResponse
    {
        try {
            $user->delete();

            return response()->json(['success' => true, 'message' => '¡Excelente! el registro se ha eliminado correctamente.']);

        } catch (\Exception $e) {
            Log::error('Error eliminando el registro ID ' . $user->id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al intentar eliminar el registro.'], 500);
        }
    }
}
