<?php

namespace Database\Seeders;

use App\Models\PropertyType;
use Illuminate\Database\Seeder;

class PropertyTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Casa',            'slug' => 'casa',            'icon' => 'home'],
            ['name' => 'Departamento',    'slug' => 'departamento',    'icon' => 'building'],
            ['name' => 'Terreno',         'slug' => 'terreno',         'icon' => 'map'],
            ['name' => 'Local comercial', 'slug' => 'local-comercial', 'icon' => 'store'],
            ['name' => 'Oficina',         'slug' => 'oficina',         'icon' => 'briefcase'],
            ['name' => 'Bodega',          'slug' => 'bodega',          'icon' => 'package'],
        ];

        foreach ($types as $type) {
            PropertyType::updateOrCreate(['slug' => $type['slug']], $type);
        }
    }
}
