<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyType;
use App\Models\State;
use Illuminate\Http\Request;

class PublicPropertyController extends Controller
{
    public function index(Request $request)
    {
        $properties = Property::query()
            ->published()
            ->with(['type', 'city', 'state', 'cover'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->string('q')->value();
                $q->where(fn ($sub) => $sub->where('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%"));
            })
            ->when($request->filled('type'), fn ($q) => $q->where('property_type_id', $request->type))
            ->when($request->filled('operation'), fn ($q) => $q->where('operation', $request->operation))
            ->when($request->filled('state'), fn ($q) => $q->where('state_id', $request->state))
            ->when($request->filled('min_price'), fn ($q) => $q->where('price', '>=', $request->min_price))
            ->when($request->filled('max_price'), fn ($q) => $q->where('price', '<=', $request->max_price))
            ->when($request->filled('bedrooms'), fn ($q) => $q->where('bedrooms', '>=', $request->bedrooms))
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('public.index', [
            'properties' => $properties,
            'types' => PropertyType::active()->orderBy('name')->get(),
            'states' => State::orderBy('name')->get(),
        ]);
    }

    public function show(Property $property)
    {
        abort_unless($property->isPublished(), 404);

        $property->load(['type', 'state', 'city', 'neighborhood', 'images', 'features', 'user']);
        $property->increment('views_count');

        $similar = Property::published()
            ->where('id', '!=', $property->id)
            ->where('property_type_id', $property->property_type_id)
            ->with(['type', 'city', 'cover'])
            ->take(3)
            ->get();

        return view('public.show', compact('property', 'similar'));
    }
}
