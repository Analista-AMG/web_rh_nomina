<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ==========================================
        // CREAR PERMISOS
        // ==========================================
        $permissions = [
            // Módulo Personas
            'personas.view',
            'personas.create',
            'personas.edit',
            'personas.delete',

            // Módulo Contratos
            'contratos.view',
            'contratos.create',
            'contratos.edit',
            'contratos.delete',
            'contratos.baja',

            // Módulo Asistencia
            'asistencia.view',
            'asistencia.edit',

            // Módulo Dashboard
            'dashboard.view',
            'dashboard.export',

            // Administración de Sistema (permisos granulares)
            'users.view',      // Ver listado de usuarios
            'users.manage',    // Asignar/remover roles (excepto Administrador)
            'roles.view',      // Ver roles y permisos
            'roles.manage',    // Crear/editar/eliminar roles
            'audit.view',      // Ver auditoría del sistema
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // ==========================================
        // CREAR ROL ADMINISTRADOR
        // ==========================================
        $admin = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        // ==========================================
        // ASIGNAR ROL ADMINISTRADOR AL PRIMER USUARIO
        // ==========================================
        $firstUser = User::first();
        if ($firstUser && !$firstUser->hasRole('Administrador')) {
            $firstUser->assignRole('Administrador');
            $this->command->info("✓ Usuario '{$firstUser->email}' asignado como Administrador");
        }

        $this->command->info('✓ Permisos creados/actualizados exitosamente');
        $this->command->info('✓ Rol Administrador con acceso total');
        $this->command->info('');
        $this->command->info('Los demás roles se crean desde la interfaz web.');
    }
}
