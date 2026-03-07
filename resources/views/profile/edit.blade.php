<x-app-layout>
    @section('title', 'Mi Perfil')

    <header class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Mi Perfil</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Gestiona tu información personal y contraseña</p>
    </header>

    @if(session('status') === 'profile-updated')
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
            class="mb-4 px-4 py-3 rounded-lg bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-sm flex items-center gap-2">
            <i class="fa-solid fa-circle-check"></i> Perfil actualizado correctamente.
        </div>
    @endif
    @if(session('status') === 'password-updated')
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
            class="mb-4 px-4 py-3 rounded-lg bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-sm flex items-center gap-2">
            <i class="fa-solid fa-circle-check"></i> Contraseña actualizada correctamente.
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Información de cuenta --}}
        <div class="bg-white dark:bg-[#273142] rounded-xl shadow-sm border border-light-border dark:border-dark-border p-6">
            <h2 class="text-base font-semibold text-gray-800 dark:text-white mb-1 flex items-center gap-2">
                <i class="fa-solid fa-user text-primary"></i> Información de cuenta
            </h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-5">Actualiza tu nombre y correo electrónico.</p>

            <form method="post" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf
                @method('patch')

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Nombre</label>
                    <p class="px-3 py-2 rounded-lg border border-light-border dark:border-dark-border bg-gray-50 dark:bg-[#1e2736]/60 text-gray-500 dark:text-gray-400 text-sm">{{ $user->name }}</p>
                </div>

                <div>
                    <label for="email" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Correo electrónico</label>
                    <input id="email" name="email" type="email"
                        value="{{ old('email', $user->email) }}"
                        class="w-full px-3 py-2 rounded-lg border border-light-border dark:border-dark-border bg-white dark:bg-[#1e2736] text-gray-800 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 @error('email') border-red-400 @enderror"
                        required>
                    @error('email')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-2">
                    <button type="submit" class="btn btn-primary text-sm px-5">
                        <i class="fa-solid fa-floppy-disk mr-1.5"></i> Guardar cambios
                    </button>
                </div>
            </form>
        </div>

        {{-- Cambio de contraseña --}}
        <div class="bg-white dark:bg-[#273142] rounded-xl shadow-sm border border-light-border dark:border-dark-border p-6">
            <h2 class="text-base font-semibold text-gray-800 dark:text-white mb-1 flex items-center gap-2">
                <i class="fa-solid fa-lock text-primary"></i> Cambiar contraseña
            </h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-5">Usa una contraseña larga y segura.</p>

            <form method="post" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                @method('put')

                <div>
                    <label for="current_password" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Contraseña actual</label>
                    <input id="current_password" name="current_password" type="password"
                        class="w-full px-3 py-2 rounded-lg border border-light-border dark:border-dark-border bg-white dark:bg-[#1e2736] text-gray-800 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 @error('current_password', 'updatePassword') border-red-400 @enderror"
                        autocomplete="current-password">
                    @error('current_password', 'updatePassword')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Nueva contraseña</label>
                    <input id="password" name="password" type="password"
                        class="w-full px-3 py-2 rounded-lg border border-light-border dark:border-dark-border bg-white dark:bg-[#1e2736] text-gray-800 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 @error('password', 'updatePassword') border-red-400 @enderror"
                        autocomplete="new-password">
                    @error('password', 'updatePassword')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Confirmar contraseña</label>
                    <input id="password_confirmation" name="password_confirmation" type="password"
                        class="w-full px-3 py-2 rounded-lg border border-light-border dark:border-dark-border bg-white dark:bg-[#1e2736] text-gray-800 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-primary/50"
                        autocomplete="new-password">
                </div>

                <div class="pt-2">
                    <button type="submit" class="btn btn-primary text-sm px-5">
                        <i class="fa-solid fa-key mr-1.5"></i> Actualizar contraseña
                    </button>
                </div>
            </form>
        </div>

    </div>

    {{-- Info adicional (solo lectura) --}}
    <div class="mt-6 bg-white dark:bg-[#273142] rounded-xl shadow-sm border border-light-border dark:border-dark-border p-6">
        <h2 class="text-base font-semibold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
            <i class="fa-solid fa-circle-info text-primary"></i> Datos del sistema
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm mb-6">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Rol del sistema</p>
                <p class="font-medium text-gray-800 dark:text-white">{{ $user->roles->first()?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">N° Documento</p>
                <p class="font-medium text-gray-800 dark:text-white">{{ $user->numero_documento ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Cuenta creada</p>
                <p class="font-medium text-gray-800 dark:text-white">{{ $user->created_at?->format('d/m/Y') ?? '—' }}</p>
            </div>
        </div>

        {{-- Asignaciones vigentes --}}
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
            <i class="fa-solid fa-user-tag text-primary text-xs"></i> Asignaciones vigentes
        </h3>
        @if($asignaciones->isEmpty())
            <p class="text-sm text-gray-400 dark:text-gray-500">Sin asignaciones activas.</p>
        @else
            <div class="space-y-2">
                @foreach($asignaciones as $a)
                <div class="flex flex-wrap gap-x-6 gap-y-1 px-3 py-2.5 rounded-lg bg-gray-50 dark:bg-[#1e2736] text-sm">
                    <div>
                        <span class="text-xs text-gray-400 dark:text-gray-500">Rol</span>
                        <p class="font-medium text-gray-800 dark:text-white">{{ $a->rol }}</p>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 dark:text-gray-500">Campaña</span>
                        <p class="font-medium text-gray-800 dark:text-white">{{ $a->campana?->nombre ?? '—' }}</p>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 dark:text-gray-500">Superior</span>
                        <p class="font-medium text-gray-800 dark:text-white">{{ $a->superior?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 dark:text-gray-500">Desde</span>
                        <p class="font-medium text-gray-800 dark:text-white">{{ $a->fecha_inicio?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

</x-app-layout>
