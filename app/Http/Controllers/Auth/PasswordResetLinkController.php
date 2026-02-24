<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\CodigoRecuperacion;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'numero_documento' => ['required', 'string'],
        ]);

        $user = User::where('numero_documento', $request->numero_documento)->first();

        // Respuesta genérica para no revelar si el documento existe
        if (!$user || !$user->email) {
            return back()->with('status', 'Si el documento está registrado, recibirás un código en tu correo.');
        }

        // Generar código de 6 dígitos
        $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Guardar hash en password_reset_tokens (reutilizamos la tabla de Laravel)
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($codigo), 'created_at' => now()]
        );

        // Enviar código por correo
        Mail::to($user->email)->send(new CodigoRecuperacion($codigo, $user->name));

        // Guardar email en sesión para el siguiente paso (no lo exponemos en la URL)
        session(['reset_email' => $user->email]);

        return redirect()->route('password.verify');
    }
}
