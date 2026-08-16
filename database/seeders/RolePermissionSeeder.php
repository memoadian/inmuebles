<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /** group => [name => description] */
    private array $permissions = [
        'properties' => [
            'properties.view'     => 'Ver el listado de propiedades',
            'properties.create'   => 'Registrar nuevas propiedades',
            'properties.edit'     => 'Editar sus propias propiedades',
            'properties.delete'   => 'Eliminar propiedades',
            'properties.publish'  => 'Publicar y despublicar propiedades',
            'properties.edit-any' => 'Editar propiedades de cualquier usuario',
        ],
        'images' => [
            'images.upload'  => 'Subir fotos a una propiedad',
            'images.delete'  => 'Eliminar fotos de una propiedad',
            'images.reorder' => 'Reordenar fotos y definir la portada',
        ],
        'catalogs' => [
            'catalogs.manage' => 'Administrar tipos de inmueble, amenidades y ubicaciones',
        ],
        'users' => [
            'users.view'   => 'Ver el listado de usuarios',
            'users.create' => 'Crear usuarios',
            'users.edit'   => 'Editar usuarios',
            'users.delete' => 'Eliminar usuarios',
        ],
        'roles' => [
            'roles.view'   => 'Ver roles',
            'roles.create' => 'Crear roles',
            'roles.edit'   => 'Editar roles',
            'roles.delete' => 'Eliminar roles',
        ],
        'permissions' => [
            'permissions.view'   => 'Ver permisos',
            'permissions.create' => 'Crear permisos',
            'permissions.edit'   => 'Editar permisos',
            'permissions.delete' => 'Eliminar permisos',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $group => $items) {
            foreach ($items as $name => $description) {
                Permission::updateOrCreate(
                    ['name' => $name, 'guard_name' => 'web'],
                    ['group' => $group, 'description' => $description]
                );
            }
        }

        $admin = Role::updateOrCreate(
            ['name' => 'Admin', 'guard_name' => 'web'],
            ['description' => 'Acceso total al sistema']
        );
        $admin->syncPermissions(Permission::all());

        $agent = Role::updateOrCreate(
            ['name' => 'Agent', 'guard_name' => 'web'],
            ['description' => 'Gestiona únicamente sus propias propiedades']
        );
        $agent->syncPermissions([
            'properties.view', 'properties.create', 'properties.edit',
            'properties.delete', 'properties.publish',
            'images.upload', 'images.delete', 'images.reorder',
        ]);

        Role::updateOrCreate(
            ['name' => 'Client', 'guard_name' => 'web'],
            ['description' => 'Consulta el catálogo público']
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
