<x-guest-layout>
    <div style="background:#fff; border-radius:30px; box-shadow:0 5px 15px rgba(0,0,0,0.35); width:420px; max-width:100%; padding:40px;">
        <div style="text-align:center; margin-bottom:24px;">
            <img src="{{ asset('img/icono_empresa.png') }}" style="width:70px; margin-bottom:12px;" alt="AMG">
            <h1 style="font-size:22px; font-weight:700; color:#333;">Nueva contraseña</h1>
            <p style="font-size:13px; color:#666; margin-top:8px;">Elige una contraseña segura para tu cuenta.</p>
        </div>

        <form method="POST" action="{{ route('password.store') }}">
            @csrf

            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:12px; font-weight:600; color:#555; margin-bottom:6px;">Nueva contraseña</label>
                <input type="password" name="password" required autocomplete="new-password"
                    placeholder="Mínimo 8 caracteres"
                    style="width:100%; background:#eee; border:none; padding:10px 15px; font-size:13px; border-radius:8px; outline:none;">
                @error('password')
                    <span style="color:#e74c3c; font-size:11px; margin-top:4px; display:block;">{{ $message }}</span>
                @enderror
            </div>

            <div style="margin-bottom:24px;">
                <label style="display:block; font-size:12px; font-weight:600; color:#555; margin-bottom:6px;">Confirmar contraseña</label>
                <input type="password" name="password_confirmation" required autocomplete="new-password"
                    placeholder="Repite la contraseña"
                    style="width:100%; background:#eee; border:none; padding:10px 15px; font-size:13px; border-radius:8px; outline:none;">
                @error('password_confirmation')
                    <span style="color:#e74c3c; font-size:11px; margin-top:4px; display:block;">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit"
                style="width:100%; background:#e67e22; color:#fff; font-size:12px; padding:12px; border:none; border-radius:8px; font-weight:600; letter-spacing:0.5px; text-transform:uppercase; cursor:pointer; transition:background 0.3s;"
                onmouseover="this.style.background='#d35400'" onmouseout="this.style.background='#e67e22'">
                <i class="fa-solid fa-lock" style="margin-right:6px;"></i>Guardar contraseña
            </button>
        </form>
    </div>
</x-guest-layout>
