<?php

namespace App\Http\Controllers\User\Auth;

use App\Models\WebUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;

class LoginUserController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::guard('web_user')->check()) {
            return redirect()->route('user.dashboard');
        }
        return view('auth.web_user_login');
    }

    public function authentication(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if (Auth::guard('web_user')->validate($credentials)) {
            $user = WebUser::where('email', $credentials['email'])->first();
            if ($user && $user->status === 'active') {
                if (Auth::guard('web_user')->attempt($credentials, $request->boolean('remember'))) {
                    $request->session()->regenerate();
                    return redirect()->intended(route('user.dashboard'));
                }
            } else if ($user && $user->status !== 'active') {
                throw ValidationException::withMessages([
                    'email' => ['Tu cuenta no se encuentra activa. Por favor, contacta al administrador.'],
                ]);
            }
        }

        throw ValidationException::withMessages([
            'email' => [trans('auth.failed')],
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web_user')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/user/login');
    }
}
