<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\User;

class AccountActivationController extends Controller
{
    public function showActivationForm($token)
    {
        $record = DB::table('account_activations')->where('token', $token)->first();

        if (!$record) {
            return redirect('/')->with('error', 'Token inválido o expirado.');
        }

        return view('auth.activate_account', ['token' => $token, 'email' => $record->email]);
    }

    public function activate(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        $record = DB::table('account_activations')->where('token', $request->token)->first();

        if (!$record) {
            return redirect('/')->with('error', 'Token inválido o expirado.');
        }

        $user = User::where('email', $record->email)->first();
        $user->password = Hash::make($request->password);
        $user->email_verified_at = now(); // Opcional: marcar como verificado
        $user->save();

        // Eliminar token
        DB::table('account_activations')->where('token', $request->token)->delete();

        return redirect('/login')->with('success', 'Cuenta activada correctamente. Ya puedes iniciar sesión.');
    }
}
