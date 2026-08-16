<?php

namespace Database\Seeders;

use App\Models\State;
use Illuminate\Database\Seeder;

class StateSeeder extends Seeder
{
    public function run(): void
    {
        $states = [
            'AGS' => 'Aguascalientes',
            'BC'  => 'Baja California',
            'BCS' => 'Baja California Sur',
            'CAM' => 'Campeche',
            'CHP' => 'Chiapas',
            'CHH' => 'Chihuahua',
            'CMX' => 'Ciudad de México',
            'COA' => 'Coahuila',
            'COL' => 'Colima',
            'DUR' => 'Durango',
            'MEX' => 'Estado de México',
            'GUA' => 'Guanajuato',
            'GRO' => 'Guerrero',
            'HID' => 'Hidalgo',
            'JAL' => 'Jalisco',
            'MIC' => 'Michoacán',
            'MOR' => 'Morelos',
            'NAY' => 'Nayarit',
            'NLE' => 'Nuevo León',
            'OAX' => 'Oaxaca',
            'PUE' => 'Puebla',
            'QUE' => 'Querétaro',
            'ROO' => 'Quintana Roo',
            'SLP' => 'San Luis Potosí',
            'SIN' => 'Sinaloa',
            'SON' => 'Sonora',
            'TAB' => 'Tabasco',
            'TAM' => 'Tamaulipas',
            'TLA' => 'Tlaxcala',
            'VER' => 'Veracruz',
            'YUC' => 'Yucatán',
            'ZAC' => 'Zacatecas',
        ];

        foreach ($states as $code => $name) {
            State::updateOrCreate(['code' => $code], ['name' => $name]);
        }
    }
}
