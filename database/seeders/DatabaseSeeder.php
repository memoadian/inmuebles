<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            PropertyTypeSeeder::class,
            FeatureSeeder::class,
            StateSeeder::class,
        ]);

        $admin = User::updateOrCreate(
            ['email' => 'admin@inmuebles.test'],
            ['name' => 'Administrador', 'password' => 'password', 'is_active' => true]
        );
        $admin->syncRoles('Admin');

        $agent = User::updateOrCreate(
            ['email' => 'agente@inmuebles.test'],
            ['name' => 'Agente Demo', 'password' => 'password', 'is_active' => true]
        );
        $agent->syncRoles('Agent');
    }
}
