<x-guest-layout>
    <div style="background:#fff; border-radius:30px; box-shadow:0 5px 15px rgba(0,0,0,0.35); width:420px; max-width:100%; padding:40px;">
        <div style="text-align:center; margin-bottom:24px;">
            <img src="{{ asset('img/icono_empresa.png') }}" style="width:70px; margin-bottom:12px;" alt="AMG">
            <h1 style="font-size:22px; font-weight:700; color:#333;">Recuperar contraseña</h1>
            <p style="font-size:13px; color:#666; margin-top:8px;">
                Ingresa tu número de documento y te enviaremos un enlace a tu correo registrado.
            </p>
        </div>

        @if(session('status'))
            <div style="background:#d4edda; border:1px solid #c3e6cb; color:#155724; border-radius:8px; padding:12px 16px; font-size:13px; margin-bottom:16px;">
                <i class="fa-solid fa-circle-check" style="margin-right:6px;"></i>{{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:12px; font-weight:600; color:#555; margin-bottom:6px;">N° Documento</label>
                <input type="text" name="numero_documento" value="{{ old('numero_documento') }}" required autofocus
                    placeholder="Ingresa tu DNI / CE"
                    style="width:100%; background:#eee; border:none; padding:10px 15px; font-size:13px; border-radius:8px; outline:none; {{ $errors->has('numero_documento') ? 'border:1px solid #e74c3c;' : '' }}">
                @error('numero_documento')
                    <span style="color:#e74c3c; font-size:11px; margin-top:4px; display:block;">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit"
                style="width:100%; background:#e67e22; color:#fff; font-size:12px; padding:12px; border:none; border-radius:8px; font-weight:600; letter-spacing:0.5px; text-transform:uppercase; cursor:pointer; transition:background 0.3s;"
                onmouseover="this.style.background='#d35400'" onmouseout="this.style.background='#e67e22'">
                <i class="fa-solid fa-paper-plane" style="margin-right:6px;"></i>Enviar enlace
            </button>
        </form>

        <div style="text-align:center; margin-top:20px;">
            <a href="{{ route('login') }}" style="font-size:13px; color:#e67e22; text-decoration:none;">
                <i class="fa-solid fa-arrow-left" style="margin-right:4px;"></i>Volver al inicio de sesión
            </a>
        </div>
    </div>
</x-guest-layout>
