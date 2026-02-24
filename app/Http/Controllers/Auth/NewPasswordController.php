<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    // Paso 2: Formulario para ingresar el código
    public function showVerify(): View|RedirectResponse
    {
        if (!session('reset_email')) {
            return redirect()->route('password.request');
        }

        return view('auth.verify-code');
    }

    // Paso 2: Validar el código ingresado
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'codigo' => ['required', 'string', 'size:6'],
        ]);

        $email = session('reset_email');

        if (!$email) {
            return redirect()->route('password.request')
                ->withErrors(['codigo' => 'Sesión expirada. Vuelve a solicitar el código.']);
        }

        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$record) {
            return back()->withErrors(['codigo' => 'Código inválido o expirado.']);
        }

        // Verificar expiración (15 minutos)
        if (now()->diffInMinutes($record->created_at) > 15) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return back()->withErrors(['codigo' => 'El código ha expirado. Solicita uno nuevo.']);
        }

        if (!Hash::check($request->codigo, $record->token)) {
            return back()->withErrors(['codigo' => 'El código ingresado no es correcto.']);
        }

        // Código válido → marcar sesión para permitir el cambio de contraseña
        session(['reset_verified' => true]);

        return redirect()->route('password.reset');
    }

    // Paso 3: Formulario nueva contraseña
    public function create(): View|RedirectResponse
    {
        if (!session('reset_email') || !session('reset_verified')) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password');
    }

    // Paso 3: Guardar nueva contraseña
    public function store(Request $request): RedirectResponse
    {
        $email = session('reset_email');

        if (!$email || !session('reset_verified')) {
            return redirect()->route('password.request');
        }

        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::where('email', $email)->first();

        if (!$user) {
            return redirect()->route('password.request');
        }

        $user->forceFill([
            'password'       => Hash::make($request->password),
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($user));

        // Limpiar token y sesión
        DB::table('password_reset_tokens')->where('email', $email)->delete();
        session()->forget(['reset_email', 'reset_verified']);

        return redirect()->route('login')->with('success', 'Contraseña actualizada correctamente. Inicia sesión.');
    }
}
