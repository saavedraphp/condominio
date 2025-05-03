<?php

namespace App\Http\Controllers;

use App\Http\Requests\PetitionRequest;
use App\Models\Petition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PetitionController extends Controller
{
    public function showPage(): View
    {
        $webUser = Auth::guard('web_user')->user();
        return view('user.petitions', [
                'webUserId' => $webUser->id,
                'urlBase' => route('user.petitions.index'),
                'isAdmin' => false,
            ]
        );
    }

    public function index(): JsonResponse
    {
        $webUser = Auth::guard('web_user')->user();
        $petitions = $webUser->petitions()->latest()->paginate(15); // O get() si no quieres paginación

        return response()->json($petitions);
    }
    public function store(PetitionRequest $request): JsonResponse
    {
        $webUser = Auth::guard('web_user')->user();
        $validatedData = $request->validated();
        try {

            $petition = $webUser->petitions()->create($validatedData);
            return response()->json([
                'success' => true,
                'message' => '¡Excelente! la petición fue registrado exitosamente.',
                'data' => $petition,
            ], JsonResponse::HTTP_CREATED);


        } catch (\exception $e) {
            $messageError = 'Ócurrio un error al intentar subir el documento. ';
            Log::error($messageError . $e->getMessage());
            return response()->json(['success' => false, 'message' => $messageError . $e->getMessage()], 500);
        }
    }

    public function show(Request $request, Petition $petition): JsonResponse
    {
        $webUser = Auth::guard('web_user')->user();
        if (!$webUser || $petition->web_user_id !== $webUser->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // Cargar respuestas y quién respondió (repliable)
        $petition->load(['replies.repliable' => function ($query) {
            // Selecciona solo los campos necesarios para evitar exponer datos sensibles
            $query->select('id', 'name'); // Ajusta 'name' según los campos en User y WebUser
        }]);


        return response()->json($petition);
    }

    public function addReply(Request $request, Petition $petition): JsonResponse
    {
        $webUser = Auth::guard('web_user')->user();
        if (!$webUser || $petition->web_user_id !== $webUser->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // Un inquilino no debería poder responder a una petición cerrada por el admin
        if ($petition->status === 'Cerrado') {
            return response()->json(['message' => 'La petición está cerrada'], 400);
        }

        $validated = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        // Crear la respuesta asociada al WebUser autenticado
        $reply = $petition->replies()->create([
            'body' => $validated['body'],
            'repliable_id' => $webUser->id,
            'repliable_type' => get_class($webUser), // O directamente App\Models\WebUser::class
        ]);

        // Opcional: Cambiar estado a 'Abierto' o 'En Progreso' si responde el inquilino
        if($petition->status !== 'Abierto') {
            $petition->update(['status' => 'Abierto']); // O 'En Progreso' según tu lógica
        }


        $reply->load('repliable:id,name'); // Carga la info del usuario que respondió

        return response()->json($reply, 201);
    }
}
