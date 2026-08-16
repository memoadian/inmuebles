<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            'Alberca', 'Jardín', 'Roof garden', 'Seguridad 24h', 'Elevador',
            'Gimnasio', 'Amueblado', 'Acepta mascotas', 'Cisterna',
            'Aire acondicionado', 'Calentador solar', 'Cocina integral',
            'Cuarto de servicio', 'Terraza', 'Vigilancia en caseta',
        ];

        foreach ($features as $name) {
            Feature::updateOrCreate(
                ['slug' => str($name)->slug()->value()],
                ['name' => $name]
            );
        }
    }
}
