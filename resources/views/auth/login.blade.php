<x-guest-layout>
    <div class="container" id="container">
        <!-- Sign Up Form -->
        <div class="form-container sign-up">
            <form method="POST" action="{{ route('register') }}">
                @csrf
                <input type="hidden" name="form_type" value="register">
                <img src="{{ asset('img/icono_empresa.png') }}" class="logo-login" alt="AMG Logo">
                <h1>Crear Cuenta</h1>
                <p>Ingresa tus datos para registrarte</p>
                
                <input type="text" name="name" placeholder="Nombre Completo" value="{{ old('name') }}" required>
                @error('name') <span class="error-message">{{ $message }}</span> @enderror
                
                <input type="email" name="email" placeholder="Correo Electrónico" value="{{ old('email') }}" required>
                @error('email') <span class="error-message">{{ $message }}</span> @enderror
                
                <input type="password" name="password" placeholder="Contraseña" required>
                <input type="password" name="password_confirmation" placeholder="Confirmar Contraseña" required>
                @error('password') <span class="error-message">{{ $message }}</span> @enderror

                <button type="submit">Registrarse</button>
            </form>
        </div>

        <!-- Sign In Form -->
        <div class="form-container sign-in">
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <input type="hidden" name="form_type" value="login">
                <img src="{{ asset('img/icono_empresa.png') }}" class="logo-login" alt="AMG Logo">
                <h1>Iniciar Sesión</h1>
                <p>Accede a AMG International</p>
                
                <input type="email" name="email" placeholder="Correo Electrónico" value="{{ old('email') }}" required autofocus>
                @error('email') <span class="error-message">{{ $message }}</span> @enderror
                
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
</x-guest-layout>
