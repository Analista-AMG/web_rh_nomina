<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Buscar persona por número de documento.
     */
    public function buscarPersona(Request $request): JsonResponse
    {
        $request->validate([
            'numero_documento' => ['required', 'string'],
        ]);

        $persona = Persona::where('numero_documento', $request->numero_documento)->first();

        if (!$persona) {
            return response()->json([
                'existe' => false,
                'mensaje' => 'No se encontró ninguna persona con este documento.'
            ]);
        }

        // Verificar si ya existe un usuario con este documento
        $usuarioExiste = User::where('numero_documento', $request->numero_documento)->exists();

        if ($usuarioExiste) {
            return response()->json([
                'existe' => true,
                'registrado' => true,
                'mensaje' => 'Ya existe una cuenta registrada con este documento.'
            ]);
        }

        return response()->json([
            'existe' => true,
            'registrado' => false,
            'persona' => [
                'id_persona' => $persona->id_persona,
                'nombre_completo' => $persona->nombre_completo,
                'email' => $persona->correo_electronico_corporativo ?? $persona->correo_electronico_personal ?? '',
            ]
        ]);
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'numero_documento' => ['required', 'string', 'max:20', 'unique:users,numero_documento'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Verificar que el documento existe en dim_persona
        $persona = Persona::where('numero_documento', $request->numero_documento)->first();

        if (!$persona) {
            return back()->withErrors([
                'numero_documento' => 'El número de documento no corresponde a ningún empleado registrado.'
            ])->withInput();
        }

        // Extraer primer nombre + apellido paterno
        $primerNombre = explode(' ', trim($persona->nombres))[0];
        $nombreCorto = $primerNombre . ' ' . $persona->apellido_paterno;

        $user = User::create([
            'numero_documento' => $request->numero_documento,
            'name' => $nombreCorto,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        return redirect(route('login'))->with('success', 'Cuenta creada exitosamente. Inicia sesión.');
    }
}
