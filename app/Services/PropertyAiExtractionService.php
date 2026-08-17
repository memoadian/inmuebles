<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class PropertyAiExtractionService
{
    private const ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';

    private const ALLOWED_OPERATIONS = ['sale', 'rent', 'both'];

    private const ALLOWED_CURRENCIES = ['MXN', 'USD'];

    private const NUMERIC_FIELDS = [
        'price', 'maintenance_fee', 'bedrooms', 'bathrooms', 'half_bathrooms',
        'parking_spaces', 'land_area', 'built_area', 'floors', 'age_years',
    ];

    private const TEXT_FIELDS = [
        'title', 'description', 'street', 'ext_number', 'int_number', 'postal_code',
    ];

    /**
     * Extrae campos estructurados de un texto libre de propiedad usando Groq (Llama 3.1 8B).
     *
     * @param  array{property_types: array<int, string>, states: array<int, string>, features: array<int, string>}  $catalog
     * @return array<string, mixed>
     */
    public function extract(string $text, array $catalog): array
    {
        $apiKey = config('services.groq.api_key');

        if (! $apiKey) {
            throw new RuntimeException('GROQ_API_KEY no está configurado.');
        }

        $response = Http::withToken($apiKey)
            ->timeout(30)
            ->post(self::ENDPOINT, [
                'model' => config('services.groq.model'),
                'temperature' => 0.1,
                'max_tokens' => 1024,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt($catalog)],
                    ['role' => 'user', 'content' => $text],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Groq respondió con error: '.$response->status().' '.$response->body());
        }

        $content = $response->json('choices.0.message.content');

        if (! $content) {
            throw new RuntimeException('Groq no devolvió contenido.');
        }

        $data = json_decode($content, true);

        if (! is_array($data)) {
            throw new RuntimeException('Groq no devolvió un JSON válido.');
        }

        return $this->sanitize($data, $catalog);
    }

    /**
     * @param  array{property_types: array<int, string>, states: array<int, string>, features: array<int, string>}  $catalog
     */
    private function systemPrompt(array $catalog): string
    {
        $types = implode(', ', $catalog['property_types']);
        $states = implode(', ', $catalog['states']);
        $features = implode(', ', $catalog['features']);

        return <<<PROMPT
            Eres un asistente que extrae datos estructurados de anuncios de bienes raíces en español para llenar un formulario. Responde ÚNICAMENTE con un objeto JSON (sin texto adicional, sin markdown).

            Claves permitidas (usa solo las que puedas determinar con confianza a partir del texto; omite el resto, no inventes datos):
            - title (string, breve)
            - description (string)
            - property_type (string, debe ser EXACTAMENTE uno de: {$types})
            - operation (string, uno de: sale, rent, both)
            - price (number, sin comas ni símbolos)
            - currency (string, uno de: MXN, USD)
            - maintenance_fee (number)
            - bedrooms (number)
            - bathrooms (number)
            - half_bathrooms (number)
            - parking_spaces (number)
            - land_area (number, m²)
            - built_area (number, m²)
            - floors (number)
            - age_years (number)
            - street (string)
            - ext_number (string)
            - int_number (string)
            - postal_code (string)
            - state (string, debe ser EXACTAMENTE uno de: {$states})
            - features (array de strings, cada uno EXACTAMENTE uno de: {$features})

            Responde solo el JSON.
            PROMPT;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array{property_types: array<int, string>, states: array<int, string>, features: array<int, string>}  $catalog
     * @return array<string, mixed>
     */
    private function sanitize(array $data, array $catalog): array
    {
        $clean = [];

        foreach (self::TEXT_FIELDS as $field) {
            if (! empty($data[$field]) && is_string($data[$field])) {
                $clean[$field] = trim($data[$field]);
            }
        }

        foreach (self::NUMERIC_FIELDS as $field) {
            if (isset($data[$field]) && is_numeric($data[$field])) {
                $clean[$field] = $data[$field] + 0;
            }
        }

        if (isset($data['operation']) && in_array($data['operation'], self::ALLOWED_OPERATIONS, true)) {
            $clean['operation'] = $data['operation'];
        }

        if (isset($data['currency']) && in_array($data['currency'], self::ALLOWED_CURRENCIES, true)) {
            $clean['currency'] = $data['currency'];
        }

        if (! empty($data['property_type']) && in_array($data['property_type'], $catalog['property_types'], true)) {
            $clean['property_type'] = $data['property_type'];
        }

        if (! empty($data['state']) && in_array($data['state'], $catalog['states'], true)) {
            $clean['state'] = $data['state'];
        }

        if (! empty($data['features']) && is_array($data['features'])) {
            $clean['features'] = array_values(array_intersect($data['features'], $catalog['features']));
        }

        return $clean;
    }
}
