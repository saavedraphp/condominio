<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WebUser;
use App\Traits\ManagesHouseSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    use ManagesHouseSession;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->clearHouseSession();
        return view('user.profile',['userId' => Auth::id()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */

    public function getUserData(): JsonResponse
    {
        $user = Auth::guard('web_user')->user();

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }
        return response()->json($user, 200);
    }

    public function update(Request $request, WebUser $profile): JsonResponse
    {
        try {
            $dataBase = $request->only(['name', 'email', 'phone']);
            if ($request->hasFile('file_path') && $request->file('file_path')->isValid()) {

                if ($profile->file_path && Storage::disk('public')->exists($profile->file_path)) {
                    Storage::disk('public')->delete($profile->file_path);
                }

                $file = $request->file('file_path');
                $dataBase['file_path'] = $file->store('file_paths/profile', 'public');

            }

            $profile->update($dataBase);

            return response()->json([
                'success' => true,
                'message' => 'Perfil actualizado correctamente',
                'data' => $profile,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al actualizar perfil'], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
