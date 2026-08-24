<?php

namespace App\Http\Controllers;

use App\Services\GeocodingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeocodingController extends Controller
{
    public function __construct(private readonly GeocodingService $geocoding) {}

    /** Dirección escrita → candidatos para el mapa, del más probable al menos. */
    public function search(Request $request): JsonResponse
    {
        $data = $request->validate([
            'address' => ['required', 'string', 'min:5', 'max:255'],
            'limit' => ['nullable', 'integer', 'between:1,5'],
        ]);

        $results = $this->geocoding->search($data['address'], (int) ($data['limit'] ?? 1));

        return $results
            ? response()->json(['results' => $results])
            : response()->json(['message' => 'No encontramos esa dirección. Mueve el pin a mano.'], 404);
    }

    /** Pin movido en el mapa → dirección sugerida (CP, colonia). */
    public function reverse(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $result = $this->geocoding->reverse((float) $data['latitude'], (float) $data['longitude']);

        return $result
            ? response()->json($result)
            : response()->json(['message' => 'Sin dirección para ese punto.'], 404);
    }
}
