<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    /**
     * Catálogo agrupado. Las listas de "amenidad" y "recreacion" salen del
     * documento de puntos faltantes del cliente; "caracteristica" agrupa las
     * etiquetas que describen al inmueble más que a sus servicios.
     *
     * @var array<string, array<int, string>>
     */
    private array $groups = [
        'amenidad' => [
            'Alberca', 'Jardín', 'Roof garden', 'Seguridad 24h', 'Elevador',
            'Gimnasio', 'Amueblado', 'Acepta mascotas', 'Cisterna',
            'Aire acondicionado', 'Calentador solar', 'Cocina integral',
            'Cocina equipada', 'Cuarto de servicio', 'Terraza',
            'Vigilancia en caseta', 'Estacionamiento para visitas',
            'Facilidad para estacionarse', 'Estacionamiento techado',
            'Muelle de carga', 'Parrilla', 'Patio', 'Vestidor',
            'Acceso para adultos mayores',
        ],
        'recreacion' => [
            'Área de juegos infantiles', 'Cancha de pádel', 'Cancha de tenis',
            'Cine', 'Fogatero', 'Jacuzzi', 'Ludoteca', 'Salón de usos múltiples',
        ],
        'caracteristica' => [
            'Una sola planta', 'Dos plantas', 'Estudio',
            'Fraccionamiento privado', 'Penthouse',
        ],
    ];

    public function run(): void
    {
        foreach ($this->groups as $group => $names) {
            foreach ($names as $index => $name) {
                Feature::updateOrCreate(
                    ['slug' => str($name)->slug()->value()],
                    ['name' => $name, 'group' => $group, 'order' => $index]
                );
            }
        }
    }
}
