<x-guest-layout>
    <div style="background:#fff; border-radius:30px; box-shadow:0 5px 15px rgba(0,0,0,0.35); width:420px; max-width:100%; padding:40px;">
        <div style="text-align:center; margin-bottom:24px;">
            <img src="{{ asset('img/icono_empresa.png') }}" style="width:70px; margin-bottom:12px;" alt="AMG">
            <h1 style="font-size:22px; font-weight:700; color:#333;">Verificar código</h1>
            <p style="font-size:13px; color:#666; margin-top:8px; line-height:1.6;">
                Te enviamos un código de 6 dígitos a tu correo registrado.<br>
                <strong>Expira en 15 minutos.</strong>
            </p>
        </div>

        <form method="POST" action="{{ route('password.verify.store') }}">
            @csrf

            <div style="margin-bottom:20px;">
                <label style="display:block; font-size:12px; font-weight:600; color:#555; margin-bottom:8px;">Código de verificación</label>
                <input type="text" name="codigo" maxlength="6" required autofocus
                    placeholder="_ _ _ _ _ _"
                    style="width:100%; background:#eee; border:none; padding:14px 15px; font-size:24px; font-weight:700; border-radius:8px; outline:none; text-align:center; letter-spacing:12px; {{ $errors->has('codigo') ? 'border:1px solid #e74c3c;' : '' }}">
                @error('codigo')
                    <span style="color:#e74c3c; font-size:11px; margin-top:4px; display:block; text-align:center;">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit"
                style="width:100%; background:#e67e22; color:#fff; font-size:12px; padding:12px; border:none; border-radius:8px; font-weight:600; letter-spacing:0.5px; text-transform:uppercase; cursor:pointer; transition:background 0.3s;"
                onmouseover="this.style.background='#d35400'" onmouseout="this.style.background='#e67e22'">
                <i class="fa-solid fa-shield-check" style="margin-right:6px;"></i>Verificar código
            </button>
        </form>

        <div style="text-align:center; margin-top:20px;">
            <a href="{{ route('password.email') }}" style="font-size:13px; color:#e67e22; text-decoration:none;">
                <i class="fa-solid fa-rotate-right" style="margin-right:4px;"></i>¿No recibiste el código? Solicitar otro
            </a>
        </div>
        <div style="text-align:center; margin-top:10px;">
            <a href="{{ route('login') }}" style="font-size:13px; color:#aaa; text-decoration:none;">
                <i class="fa-solid fa-arrow-left" style="margin-right:4px;"></i>Volver al inicio de sesión
            </a>
        </div>
    </div>
</x-guest-layout>
