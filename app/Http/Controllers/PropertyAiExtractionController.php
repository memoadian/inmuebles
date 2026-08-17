<?php

namespace App\Http\Controllers;

use App\Exceptions\AiExtractionException;
use App\Models\Feature;
use App\Models\PropertyType;
use App\Models\State;
use App\Services\PropertyAiExtractionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class PropertyAiExtractionController extends Controller
{
    public function __invoke(Request $request, PropertyAiExtractionService $service)
    {
        // Ayuda de IA disponible tanto en el alta como en la edición de propiedades.
        abort_unless($request->user()->can('properties.create') || $request->user()->can('properties.edit'), 403);

        $data = $request->validate([
            'text' => ['required', 'string', 'max:2500'],
        ]);

        $catalog = [
            'property_types' => PropertyType::active()->orderBy('name')->pluck('name')->all(),
            'states' => State::orderBy('name')->pluck('name')->all(),
            'features' => Feature::active()->orderBy('name')->pluck('name')->all(),
        ];

        try {
            $extracted = $service->extract($data['text'], $catalog);
        } catch (AiExtractionException $e) {
            // El detalle real (status, body de Groq) sólo va al log; al usuario
            // le llega un mensaje seguro y accionable, nunca la respuesta cruda.
            report($e);

            return response()->json(['message' => $e->userMessage()], $e->httpStatus());
        } catch (Throwable $e) {
            Log::error('ai_extract.unexpected', ['exception' => $e->getMessage()]);

            return response()->json(['message' => 'Ocurrió un error inesperado. Intenta de nuevo.'], 500);
        }

        return response()->json($extracted);
    }
}
