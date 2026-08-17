<?php

namespace App\Services;

use App\Contracts\AiChatProvider;
use App\Exceptions\AiExtractionException;
use App\Services\Ai\AiChatResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * A diferencia de PropertyAiExtractionService (que extrae datos de texto libre
 * sin validar), este servicio parte de campos YA CONFIRMADOS por el usuario en
 * el formulario. Por eso el prompt permite lenguaje de marketing (adjetivos
 * atractivos) en vez de exigir literalidad estricta — la única regla dura es
 * no afirmar hechos que no vengan en los datos.
 */
class PropertyTitleSuggestionService
{
    private const MAX_TITLES = 5;

    public function __construct(private readonly AiChatProvider $provider) {}

    /**
     * @param  array<string, mixed>  $facts  Campos ya confirmados del formulario.
     * @return array<int, string>
     *
     * @throws AiExtractionException
     */
    public function suggest(array $facts): array
    {
        $startedAt = microtime(true);

        try {
            $result = $this->provider->complete(
                $this->systemPrompt(),
                $this->factsToText($facts),
                $this->jsonSchema(),
            );
        } catch (AiExtractionException $e) {
            $this->logAttempt($startedAt, null, 'error');

            throw $e;
        }

        $data = json_decode($result->content, true);

        if (! is_array($data) || ! is_array($data['titles'] ?? null)) {
            $this->logAttempt($startedAt, $result, 'invalid_json');

            throw new AiExtractionException(
                'El proveedor de IA no devolvió una lista de títulos válida: '.json_last_error_msg(),
                502,
                'No se pudieron generar títulos. Intenta de nuevo.',
            );
        }

        $titles = $this->sanitize($data['titles']);

        if (empty($titles)) {
            $this->logAttempt($startedAt, $result, 'empty_result');

            throw new AiExtractionException(
                'El proveedor de IA devolvió una lista de títulos vacía.',
                502,
                'No se pudieron generar títulos con esos datos. Intenta con más información.',
            );
        }

        $this->logAttempt($startedAt, $result, 'ok');

        return $titles;
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    private function factsToText(array $facts): string
    {
        $labels = [
            'property_type' => 'Tipo de inmueble',
            'operation' => 'Operación',
            'price' => 'Precio',
            'currency' => 'Moneda',
            'state' => 'Estado',
            'bedrooms' => 'Recámaras',
            'bathrooms' => 'Baños',
            'built_area' => 'Construcción (m²)',
            'land_area' => 'Terreno (m²)',
        ];

        $lines = [];

        foreach ($labels as $key => $label) {
            if (! empty($facts[$key])) {
                $lines[] = "{$label}: {$facts[$key]}";
            }
        }

        if (! empty($facts['features']) && is_array($facts['features'])) {
            $lines[] = 'Amenidades: '.implode(', ', $facts['features']);
        }

        return $lines ? implode("\n", $lines) : 'Sin datos adicionales.';
    }

    private function systemPrompt(): string
    {
        return <<<PROMPT
            Eres un redactor de marketing inmobiliario en español de México. Recibes datos YA CONFIRMADOS de una propiedad (no texto libre sin revisar) y generas 3 títulos llamativos y distintos entre sí para anunciarla.

            Reglas estrictas:
            - Usa ÚNICAMENTE los datos que se te dan. No inventes amenidades, ubicación, número de recámaras/baños/m² ni ningún hecho que no aparezca en los datos.
            - Sí puedes usar adjetivos atractivos genéricos (hermosa, ideal, exclusiva, acogedora, excelente oportunidad, etc.) aunque no vengan en los datos — eso es marketing normal, siempre que no afirmes un hecho falso o específico que no se dio.
            - Si los datos son muy limitados (por ejemplo, solo tipo de inmueble y operación), igual genera 3 títulos atractivos usando solo esos datos y adjetivos genéricos — nunca devuelvas un array vacío ni te niegues a generar por falta de información.
            - Cada título debe tener máximo 80 caracteres.
            - Los 3 títulos deben tener enfoques distintos entre sí (por ejemplo: uno resalta ubicación, otro precio/oportunidad, otro características).
            - No uses emojis ni signos de exclamación excesivos.
            - Responde solo el JSON con la clave "titles": un array con exactamente 3 strings.
            PROMPT;
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'titles' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
            'required' => ['titles'],
            'additionalProperties' => false,
        ];
    }

    /**
     * @param  array<int, mixed>  $titles
     * @return array<int, string>
     */
    private function sanitize(array $titles): array
    {
        return collect($titles)
            ->filter(fn ($title) => is_string($title) && trim($title) !== '')
            ->map(fn ($title) => Str::limit(trim($title), 90, ''))
            ->unique()
            ->take(self::MAX_TITLES)
            ->values()
            ->all();
    }

    private function logAttempt(float $startedAt, ?AiChatResult $result, string $outcome): void
    {
        Log::info('ai_title_suggest', [
            'user_id' => auth()->id(),
            'provider' => $this->provider->name(),
            'outcome' => $outcome,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'prompt_tokens' => $result?->promptTokens,
            'completion_tokens' => $result?->completionTokens,
        ]);
    }
}
