<?php

namespace App\Http\Controllers;

use App\Exceptions\AiExtractionException;
use App\Services\PropertyTitleSuggestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class PropertyTitleSuggestionController extends Controller
{
    public function __invoke(Request $request, PropertyTitleSuggestionService $service)
    {
        // Disponible tanto en el alta como en la edición de propiedades.
        abort_unless($request->user()->can('properties.create') || $request->user()->can('properties.edit'), 403);

        $data = $request->validate([
            'property_type' => ['nullable', 'string', 'max:100'],
            'operation' => ['nullable', 'string', 'in:sale,rent,both'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'in:MXN,USD'],
            'state' => ['nullable', 'string', 'max:100'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:99'],
            'bathrooms' => ['nullable', 'integer', 'min:0', 'max:99'],
            'built_area' => ['nullable', 'numeric', 'min:0'],
            'land_area' => ['nullable', 'numeric', 'min:0'],
            'features' => ['nullable', 'array', 'max:30'],
            'features.*' => ['string', 'max:100'],
        ]);

        if (empty($data['property_type']) && empty($data['operation'])) {
            return response()->json([
                'message' => 'Llena al menos el tipo de inmueble y la operación antes de pedir sugerencias.',
            ], 422);
        }

        try {
            $titles = $service->suggest($data);
        } catch (AiExtractionException $e) {
            report($e);

            return response()->json(['message' => $e->userMessage()], $e->httpStatus());
        } catch (Throwable $e) {
            Log::error('ai_title_suggest.unexpected', ['exception' => $e->getMessage()]);

            return response()->json(['message' => 'Ocurrió un error inesperado. Intenta de nuevo.'], 500);
        }

        return response()->json(['titles' => $titles]);
    }
}
