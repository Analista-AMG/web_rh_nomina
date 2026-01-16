<x-guest-layout>
    <div class="container" id="container">
        <!-- Sign Up Form -->
        <div class="form-container sign-up">
            <form method="POST" action="{{ route('register') }}" id="register-form">
                @csrf
                <input type="hidden" name="form_type" value="register">
                <h1>Crear Cuenta</h1>
                <p>Ingresa tus datos para registrarte</p>

                <div class="input-group" style="width: 100%;">
                    <input type="text" name="numero_documento" id="reg-documento" placeholder="N° Documento" value="{{ old('numero_documento') }}" required>
                    <span id="documento-status" class="status-message"></span>
                    @error('numero_documento') <span class="error-message">{{ $message }}</span> @enderror
                </div>

                <input type="text" name="name" id="reg-name" placeholder="Nombre Completo" value="{{ old('name') }}" required readonly style="background-color: #f5f5f5;">
                @error('name') <span class="error-message">{{ $message }}</span> @enderror

                <input type="email" name="email" id="reg-email" placeholder="Correo Electrónico" value="{{ old('email') }}" required>
                @error('email') <span class="error-message">{{ $message }}</span> @enderror

                <input type="password" name="password" placeholder="Contraseña" required>
                <input type="password" name="password_confirmation" placeholder="Confirmar Contraseña" required>
                @error('password') <span class="error-message">{{ $message }}</span> @enderror

                <button type="submit" id="btn-register" disabled style="opacity: 0.5; cursor: not-allowed;">Registrarse</button>
            </form>
        </div>

        <!-- Sign In Form -->
        <div class="form-container sign-in">
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <input type="hidden" name="form_type" value="login">
                <img src="{{ asset('img/icono_empresa.png') }}" class="logo-login" alt="AMG Logo">
                <h1>Iniciar Sesión</h1>
                @if(session('success'))
                    <p style="color: #28a745; font-weight: 500; font-size: 12px;">{{ session('success') }}</p>
                @else
                    <p>Accede a AMG International</p>
                @endif
                
                <input type="text" name="numero_documento" placeholder="N° Documento" value="{{ old('numero_documento') }}" required autofocus>
                @error('numero_documento') <span class="error-message">{{ $message }}</span> @enderror
                
                <input type="password" name="password" placeholder="Contraseña" required>
                @error('password') <span class="error-message">{{ $message }}</span> @enderror
                
                <a href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
                <button type="submit">Ingresar</button>
            </form>
        </div>

        <!-- Toggle Panels -->
        <div class="toggle-container">
            <div class="toggle">
                <div class="toggle-panel toggle-left">
                    <h1>¡Bienvenido de nuevo!</h1>
                    <p>Si ya tienes una cuenta, inicia sesión para gestionar la nómina.</p>
                    <button class="btn-ghost" id="login">Iniciar Sesión</button>
                </div>
                <div class="toggle-panel toggle-right">
                    <h1>¡Hola!</h1>
                    <p>¿Aún no tienes cuenta? Regístrate para acceder al sistema AMG.</p>
                    <button class="btn-ghost" id="register">Registrarse</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const documentoInput = document.getElementById('reg-documento');
            const nameInput = document.getElementById('reg-name');
            const emailInput = document.getElementById('reg-email');
            const btnRegister = document.getElementById('btn-register');
            const statusSpan = document.getElementById('documento-status');

            let timeout = null;
            let personaValida = false;

            if (documentoInput) {
                documentoInput.addEventListener('input', function() {
                    clearTimeout(timeout);
                    const documento = this.value.trim();

                    // Reset
                    personaValida = false;
                    btnRegister.disabled = true;
                    btnRegister.style.opacity = '0.5';
                    btnRegister.style.cursor = 'not-allowed';
                    nameInput.value = '';
                    emailInput.value = '';
                    statusSpan.textContent = '';
                    statusSpan.className = 'status-message';

                    if (documento.length < 8) {
                        return;
                    }

                    statusSpan.textContent = 'Buscando...';
                    statusSpan.style.color = '#666';

                    timeout = setTimeout(() => {
                        fetch('{{ route("buscar.persona") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ numero_documento: documento })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (!data.existe) {
                                statusSpan.textContent = 'No se encontró empleado con este documento';
                                statusSpan.style.color = '#dc3545';
                                nameInput.value = '';
                            } else if (data.registrado) {
                                statusSpan.textContent = 'Ya existe una cuenta con este documento';
                                statusSpan.style.color = '#dc3545';
                                nameInput.value = '';
                            } else {
                                statusSpan.textContent = 'Empleado encontrado';
                                statusSpan.style.color = '#28a745';
                                nameInput.value = data.persona.nombre_completo;
                                emailInput.value = data.persona.email || '';
                                personaValida = true;
                                btnRegister.disabled = false;
                                btnRegister.style.opacity = '1';
                                btnRegister.style.cursor = 'pointer';
                            }
                        })
                        .catch(error => {
                            statusSpan.textContent = 'Error al buscar';
                            statusSpan.style.color = '#dc3545';
                            console.error('Error:', error);
                        });
                    }, 500);
                });
            }
        });
    </script>
    @endpush
</x-guest-layout>
