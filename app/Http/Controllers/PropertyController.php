<?php

namespace App\Http\Controllers;

use App\Http\Requests\PropertyRequest;
use App\Models\Feature;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Property::class);

        $user = $request->user();

        $properties = Property::query()
            ->with(['type', 'city', 'cover'])
            // Un Agent sólo ve su propio inventario.
            ->unless($user->can('properties.edit-any'), fn ($q) => $q->ownedBy($user))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->string('search')->value();
                $q->where(fn ($sub) => $sub->where('title', 'like', "%{$term}%")
                    ->orWhere('street', 'like', "%{$term}%"));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('type'), fn ($q) => $q->where('property_type_id', $request->type))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('properties.index', [
            'properties' => $properties,
            'types' => PropertyType::active()->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Property::class);

        return view('properties.create', $this->formData());
    }

    public function store(PropertyRequest $request)
    {
        $this->authorize('create', Property::class);

        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $data['slug'] = $this->uniqueSlug($data['title']);

        if ($data['status'] === 'published') {
            $data['published_at'] = now();
        }

        $property = Property::create($data);
        $property->features()->sync($request->input('features', []));

        return redirect()->route('properties.edit', $property)
            ->with('success', 'Propiedad creada. Ahora puedes agregar fotos.');
    }

    public function show(Property $property)
    {
        $this->authorize('view', $property);

        $property->load(['type', 'state', 'city', 'neighborhood', 'images', 'features', 'user']);

        return view('properties.show', compact('property'));
    }

    public function edit(Property $property)
    {
        $this->authorize('update', $property);

        $property->load(['images', 'features']);

        return view('properties.edit', [
            'property' => $property,
            ...$this->formData(),
        ]);
    }

    public function update(PropertyRequest $request, Property $property)
    {
        $this->authorize('update', $property);

        $data = $request->validated();

        if ($data['title'] !== $property->title) {
            $data['slug'] = $this->uniqueSlug($data['title'], $property->id);
        }

        // Sellar la fecha la primera vez que se publica.
        if ($data['status'] === 'published' && ! $property->published_at) {
            $data['published_at'] = now();
        }

        $property->update($data);
        $property->features()->sync($request->input('features', []));

        return redirect()->route('properties.edit', $property)
            ->with('success', 'Propiedad actualizada.');
    }

    public function destroy(Property $property)
    {
        $this->authorize('delete', $property);

        $property->delete();

        return redirect()->route('properties.index')
            ->with('success', 'Propiedad eliminada.');
    }

    public function togglePublish(Property $property)
    {
        $this->authorize('publish', $property);

        $publishing = ! $property->isPublished();

        $property->update([
            'status' => $publishing ? 'published' : 'draft',
            'published_at' => $publishing ? ($property->published_at ?? now()) : $property->published_at,
        ]);

        return back()->with('success', $publishing ? 'Propiedad publicada.' : 'Propiedad despublicada.');
    }

    private function formData(): array
    {
        return [
            'types' => PropertyType::active()->orderBy('name')->get(),
            'features' => Feature::active()->orderBy('name')->get(),
            'states' => State::orderBy('name')->get(),
        ];
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 2;

        while (Property::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
