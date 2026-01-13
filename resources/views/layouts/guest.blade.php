<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'AMG International') }}</title>
        <link rel="icon" href="{{ asset('img/icono_empresa.png') }}" type="image/png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-family: 'Montserrat', sans-serif;
            }

            body {
                background-color: #f4f6f8;
                background: linear-gradient(to right, #e2e2e2, #f4f6f8);
                display: flex;
                align-items: center;
                justify-content: center;
                flex-direction: column;
                height: 100vh;
            }

            .container {
                background-color: #fff;
                border-radius: 30px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.35);
                position: relative;
                overflow: hidden;
                width: 768px;
                max-width: 100%;
                min-height: 520px;
            }

            .container p {
                font-size: 14px;
                line-height: 20px;
                letter-spacing: 0.3px;
                margin: 20px 0;
            }

            .container span {
                font-size: 12px;
            }

            .container a {
                color: #333;
                font-size: 13px;
                text-decoration: none;
                margin: 15px 0 10px;
            }

            .container button {
                background-color: #e67e22;
                color: #fff;
                font-size: 12px;
                padding: 10px 45px;
                border: 1px solid transparent;
                border-radius: 8px;
                font-weight: 600;
                letter-spacing: 0.5px;
                text-transform: uppercase;
                margin-top: 10px;
                cursor: pointer;
                transition: all 0.3s ease;
            }

            .container button:hover {
                background-color: #d35400;
            }

            .container button.btn-ghost {
                background-color: transparent;
                border-color: #fff;
            }

            .container button.btn-ghost:hover {
                background-color: #fff;
                color: #e67e22;
            }

            .container form {
                background-color: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-direction: column;
                padding: 0 40px;
                height: 100%;
            }

            .container input {
                background-color: #eee;
                border: none;
                margin: 8px 0;
                padding: 10px 15px;
                font-size: 13px;
                border-radius: 8px;
                width: 100%;
                outline: none;
            }

            .form-container {
                position: absolute;
                top: 0;
                height: 100%;
                transition: all 0.6s ease-in-out;
            }

            .sign-in {
                left: 0;
                width: 50%;
                z-index: 2;
            }

            .container.active .sign-in {
                transform: translateX(100%);
            }

            .sign-up {
                left: 0;
                width: 50%;
                opacity: 0;
                z-index: 1;
            }

            .container.active .sign-up {
                transform: translateX(100%);
                opacity: 1;
                z-index: 5;
                animation: move 0.6s;
            }

            @keyframes move {
                0%, 49.99% {
                    opacity: 0;
                    z-index: 1;
                }
                50%, 100% {
                    opacity: 1;
                    z-index: 5;
                }
            }

            .toggle-container {
                position: absolute;
                top: 0;
                left: 50%;
                width: 50%;
                height: 100%;
                overflow: hidden;
                transition: all 0.6s ease-in-out;
                border-radius: 150px 0 0 100px;
                z-index: 1000;
            }

            .container.active .toggle-container {
                transform: translateX(-100%);
                border-radius: 0 150px 100px 0;
            }

            .toggle {
                background-color: #e67e22;
                height: 100%;
                background: linear-gradient(to right, #f39c12, #e67e22);
                color: #fff;
                position: relative;
                left: -100%;
                height: 100%;
                width: 200%;
                transform: translateX(0);
                transition: all 0.6s ease-in-out;
            }

            .container.active .toggle {
                transform: translateX(50%);
            }

            .toggle-panel {
                position: absolute;
                width: 50%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-direction: column;
                padding: 0 30px;
                text-align: center;
                top: 0;
                transform: translateX(0);
                transition: all 0.6s ease-in-out;
            }

            .toggle-left {
                transform: translateX(-200%);
            }

            .container.active .toggle-left {
                transform: translateX(0);
            }

            .toggle-right {
                right: 0;
                transform: translateX(0);
            }

            .container.active .toggle-right {
                transform: translateX(200%);
            }

            .error-message {
                color: #e74c3c;
                font-size: 11px;
                margin-top: -5px;
                margin-bottom: 5px;
                width: 100%;
                text-align: left;
            }
            
            .logo-login {
                width: 80px;
                margin-bottom: 10px;
            }
        </style>
    </head>
    <body>
        {{ $slot }}

        <script>
            const container = document.getElementById('container');
            const registerBtn = document.getElementById('register');
            const loginBtn = document.getElementById('login');

            if (registerBtn && loginBtn && container) {
                registerBtn.addEventListener('click', () => {
                    container.classList.add("active");
                });

                loginBtn.addEventListener('click', () => {
                    container.classList.remove("active");
                });
            }

            // Manejar errores de validación para mantener el estado del panel
            @if($errors->has('name') || $errors->has('email') && old('form_type') === 'register')
                if(container) container.classList.add("active");
            @endif
        </script>
    </body>
</html>
