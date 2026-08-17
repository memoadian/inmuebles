<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use App\Models\PropertyType;
use App\Models\State;
use App\Services\PropertyAiExtractionService;
use Illuminate\Http\Request;
use RuntimeException;

class PropertyAiExtractionController extends Controller
{
    public function __invoke(Request $request, PropertyAiExtractionService $service)
    {
        // Ayuda de IA disponible tanto en el alta como en la edición de propiedades.
        abort_unless($request->user()->can('properties.create') || $request->user()->can('properties.edit'), 403);

        $data = $request->validate([
            'text' => ['required', 'string', 'max:5000'],
        ]);

        $catalog = [
            'property_types' => PropertyType::active()->orderBy('name')->pluck('name')->all(),
            'states' => State::orderBy('name')->pluck('name')->all(),
            'features' => Feature::active()->orderBy('name')->pluck('name')->all(),
        ];

        try {
            $extracted = $service->extract($data['text'], $catalog);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->json($extracted);
    }
}
