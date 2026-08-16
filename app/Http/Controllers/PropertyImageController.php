<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyImage;
use App\Services\PropertyImageService;
use Illuminate\Http\Request;

class PropertyImageController extends Controller
{
    public function __construct(private PropertyImageService $service) {}

    public function store(Request $request, Property $property)
    {
        $this->authorize('uploadImages', $property);

        $request->validate([
            'images' => ['required', 'array', 'max:20'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ], [], ['images' => 'fotos']);

        foreach ($request->file('images') as $file) {
            $this->service->store($property, $file);
        }

        return back()->with('success', 'Fotos agregadas.');
    }

    public function destroy(Property $property, PropertyImage $image)
    {
        $this->authorize('deleteImages', $property);
        abort_unless($image->property_id === $property->id, 404);

        $this->service->delete($image);

        return back()->with('success', 'Foto eliminada.');
    }

    public function reorder(Request $request, Property $property)
    {
        $this->authorize('reorderImages', $property);

        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:property_images,id'],
            'cover_id' => ['nullable', 'integer', 'exists:property_images,id'],
        ]);

        $this->service->reorder($property, $data['order'], $data['cover_id'] ?? null);

        return back()->with('success', 'Orden actualizado.');
    }
}
