<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WebUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ImpersonateController extends Controller
{
    /**
     * Genera un token de un solo uso y redirige a la URL de login.
     */
    public function generateTokenAndRedirect(WebUser $webUser)
    {

        // Generar un token único y seguro
        $token = Str::random(40);

        // Almacenar el ID del usuario en la caché con el token como clave.
        // Le damos una vida corta (ej. 60 segundos) para mayor seguridad.
        Cache::put('impersonate_token_' . $token, $webUser->id, 60);

        // Redirigir a la ruta que usará el token para iniciar sesión.
        // Esta redirección ocurrirá en la nueva pestaña.
        return redirect()->route('login.with_token', ['token' => $token]);
    }

    public function loginWithToken($token)
    {
        // 1. Busca el ID del usuario en la caché.
        $userId = Cache::get('impersonate_token_' . $token);

        // 2. Si el token no existe o ha expirado, aborta.
        if (!$userId) {
            return redirect()->route('user.login')->withErrors(['email' => 'El enlace ha expirado o no es válido.']);
        }

        // 3. ¡Importante! Elimina el token para que solo se pueda usar una vez.
        Cache::forget('impersonate_token_' . $token);

        // 4. Inicia sesión para el guard 'web_user' con el ID encontrado.
        Auth::guard('web_user')->loginUsingId($userId);

        // 5. Regenera la sesión por seguridad.
        request()->session()->regenerate();

        // 6. Redirige al usuario a su panel de control.
        return redirect('/user/houses/list');
    }

}
