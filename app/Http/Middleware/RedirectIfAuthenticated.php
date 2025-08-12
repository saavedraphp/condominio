<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;
        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();
                // Determinar a dónde redirigir según el guard
                if ($user->hasRole('admin')) {
                    return redirect()->route('admin.dashboard');
                }
                if ($user->hasRole('security')) {
                    return redirect()->route('security.scan-pass');
                }
                if ($guard === 'web_user') {
                    return redirect()->route('user.dashboard'); // Asumiendo que esta es la ruta para propietarios
                }
                if ($guard === 'tenant') {
                    return redirect()->route('tenant.dashboard'); // O usa RouteServiceProvider::TENANT_HOME
                }

                return redirect(RouteServiceProvider::HOME);
            }
        }

        return $next($request);
    }
}
