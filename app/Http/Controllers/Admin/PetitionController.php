<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Petition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PetitionController extends Controller
{
    public function showPage(): View
    {
        return view('admin.petitions', [
                'urlBase' => route('admin.petitions.index'),
            ]
        );
    }

    public function index(Request $request): JsonResponse
    {
        $query = Petition::with('webUser:id,name')
        ->latest();

        // Filtrado (Ejemplos)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('subject', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%")
                    ->orWhereHas('webUser', function($q2) use ($searchTerm) {
                        $q2->where('name', 'like', "%{$searchTerm}%"); // Asume campo 'name' en WebUser
                    });
            });
        }


        $petitions = $query->paginate($request->input('per_page', 10));

        return response()->json($petitions);
    }

    // Mostrar una petición específica para el admin
    public function show(Petition $petition): JsonResponse
    {
        // Cargar inquilino, respuestas y quién respondió
        $petition->load([
            'webUser:id,name,email', // Carga más datos del inquilino si es necesario
            'replies.repliable' => function ($query) {
                // Selecciona solo campos necesarios
                $query->select('id', 'name'); // Ajusta 'name'
            }
        ]);

        return response()->json($petition);
    }

    // Añadir una respuesta como administrador
    public function addReply(Request $request, Petition $petition): JsonResponse
    {
        $adminUser = Auth::guard('web')->user(); // O el guard que uses para admin ('sanctum', 'web' si es el mismo)
        if (!$adminUser) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        $validated = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        // Crear la respuesta asociada al User (Admin) autenticado
        $reply = $petition->replies()->create([
            'body' => $validated['body'],
            'repliable_id' => $adminUser->id,
            'repliable_type' => get_class($adminUser), // O App\Models\User::class
        ]);

        // Cambiar estado a 'En Progreso' cuando el admin responde (si estaba 'Abierto')
        if ($petition->status === 'Abierto') {
            $petition->update(['status' => 'En Progreso']);
        }

        $reply->load('repliable:id,name'); // Carga info del admin que respondió

        return response()->json($reply, 201);
    }

    // Actualizar el estado de una petición
    public function updateStatus(Request $request, Petition $petition): JsonResponse
    {
        $adminUser = Auth::guard('web')->user(); // O el guard que uses
        if (!$adminUser) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['Abierto', 'En Progreso', 'Cerrado'])],
        ]);

        $petition->update(['status' => $validated['status']]);

        // Opcional: añadir una respuesta automática al cambiar estado?
        // if ($validated['status'] === 'Cerrado') {
        //     $petition->replies()->create([ ... ]);
        // }


        return response()->json($petition->only(['id', 'status'])); // Devuelve solo lo necesario
    }
}
