<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FeatureController extends Controller
{
    public function index()
    {
        return view('features.index', [
            'features' => Feature::withCount('properties')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('features.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['name']);

        Feature::create($data);

        return redirect()->route('features.index')->with('success', 'Amenidad creada.');
    }

    public function edit(Feature $feature)
    {
        return view('features.edit', compact('feature'));
    }

    public function update(Request $request, Feature $feature)
    {
        $data = $this->validated($request, $feature->id);
        $data['slug'] = Str::slug($data['name']);

        $feature->update($data);

        return redirect()->route('features.index')->with('success', 'Amenidad actualizada.');
    }

    public function destroy(Feature $feature)
    {
        $feature->delete();

        return redirect()->route('features.index')->with('success', 'Amenidad eliminada.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('features')->ignore($ignoreId)],
            'icon' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
