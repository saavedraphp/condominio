<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginUserController extends Controller
{
    private $settings;
    public function __construct()
    {

        $this->settings = Setting::query()
            ->where('group', 'general')
            ->pluck('value', 'key')
            ->toArray();

    }
    public function showLoginForm()
    {
        $settings = $this->settings;

        return view('auth.login', compact('settings'));
    }

    public function authentication(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if (Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = Auth::guard('web')->user();

            if ($user->hasRole('admin')) {
                return redirect()->intended(route('admin.dashboard'));
            } else if ($user->hasRole('security')) {
                return redirect()->intended(route('security.dashboard'));
            } else {
                // Si no es ni admin ni vigilante, es mejor desloguearlo por seguridad.
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect('/admin/login')->withErrors(['email' => 'Tu rol no permite el acceso.']);
            }

        }

        throw ValidationException::withMessages([
            'email' => [trans('auth.failed')],
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
