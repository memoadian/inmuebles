<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización real vive en PropertyPolicy, aplicada en el controlador.
        return true;
    }

    public function rules(): array
    {
        return [
            'property_type_id' => ['required', 'exists:property_types,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],

            'operation' => ['required', Rule::in(['sale', 'rent', 'both'])],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'currency' => ['required', Rule::in(['MXN', 'USD'])],
            'maintenance_fee' => ['nullable', 'numeric', 'min:0'],

            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:99'],
            'bathrooms' => ['nullable', 'integer', 'min:0', 'max:99'],
            'half_bathrooms' => ['nullable', 'integer', 'min:0', 'max:99'],
            'parking_spaces' => ['nullable', 'integer', 'min:0', 'max:99'],

            'land_area' => ['nullable', 'numeric', 'min:0'],
            'built_area' => ['nullable', 'numeric', 'min:0'],
            'floors' => ['nullable', 'integer', 'min:0', 'max:200'],
            'age_years' => ['nullable', 'integer', 'min:0', 'max:500'],

            'street' => ['nullable', 'string', 'max:255'],
            'ext_number' => ['nullable', 'string', 'max:20'],
            'int_number' => ['nullable', 'string', 'max:20'],
            'postal_code' => ['nullable', 'string', 'max:10'],

            'state_id' => ['nullable', 'exists:states,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'neighborhood_id' => ['nullable', 'exists:neighborhoods,id'],

            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],

            'status' => ['required', Rule::in(['draft', 'published', 'reserved', 'sold', 'rented', 'inactive'])],
            'is_featured' => ['nullable', 'boolean'],

            'features' => ['nullable', 'array'],
            'features.*' => ['exists:features,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'property_type_id' => 'tipo de inmueble',
            'title' => 'título',
            'price' => 'precio',
            'currency' => 'moneda',
            'operation' => 'operación',
            'bedrooms' => 'recámaras',
            'bathrooms' => 'baños',
            'land_area' => 'superficie de terreno',
            'built_area' => 'superficie construida',
        ];
    }
}
