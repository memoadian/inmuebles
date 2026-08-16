<?php

namespace App\Http\Controllers;

use App\Models\PropertyType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PropertyTypeController extends Controller
{
    public function index()
    {
        return view('property-types.index', [
            'types' => PropertyType::withCount('properties')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('property-types.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['name']);

        PropertyType::create($data);

        return redirect()->route('property-types.index')
            ->with('success', 'Tipo de inmueble creado.');
    }

    public function edit(PropertyType $propertyType)
    {
        return view('property-types.edit', ['type' => $propertyType]);
    }

    public function update(Request $request, PropertyType $propertyType)
    {
        $data = $this->validated($request, $propertyType->id);
        $data['slug'] = Str::slug($data['name']);

        $propertyType->update($data);

        return redirect()->route('property-types.index')
            ->with('success', 'Tipo de inmueble actualizado.');
    }

    public function destroy(PropertyType $propertyType)
    {
        if ($propertyType->properties()->exists()) {
            return back()->with('error', 'No se puede eliminar: hay propiedades usando este tipo.');
        }

        $propertyType->delete();

        return redirect()->route('property-types.index')
            ->with('success', 'Tipo de inmueble eliminado.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('property_types')->ignore($ignoreId)],
            'icon' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
