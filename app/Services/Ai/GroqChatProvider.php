<?php

namespace App\Services\Ai;

use App\Contracts\AiChatProvider;
use App\Exceptions\AiExtractionException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GroqChatProvider implements AiChatProvider
{
    private const ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';

    public function name(): string
    {
        return 'groq';
    }

    public function complete(string $systemPrompt, string $userText, ?array $jsonSchema = null): AiChatResult
    {
        $apiKey = config('services.groq.api_key');

        if (! $apiKey) {
            throw new AiExtractionException(
                'GROQ_API_KEY no está configurado.',
                502,
                'El autocompletado con IA no está disponible por el momento.',
            );
        }

        $responseFormat = $jsonSchema
            ? [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'property_extraction',
                    'strict' => true,
                    'schema' => $jsonSchema,
                ],
            ]
            : ['type' => 'json_object'];

        try {
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post(self::ENDPOINT, [
                    'model' => config('services.groq.model'),
                    'temperature' => 0.1,
                    'max_tokens' => 1536,
                    // Modelos "gpt-oss" razonan antes de responder y esos tokens de
                    // razonamiento cuentan contra max_tokens. Para una extracción
                    // simple como esta, "low" reduce el razonamiento ~85% sin
                    // perder calidad y evita truncar el JSON a la mitad.
                    'reasoning_effort' => 'low',
                    'response_format' => $responseFormat,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userText],
                    ],
                ]);
        } catch (ConnectionException $e) {
            throw new AiExtractionException(
                'Timeout o error de conexión con Groq: '.$e->getMessage(),
                504,
                'No se pudo conectar con el servicio de IA. Intenta de nuevo en unos segundos.',
                $e,
            );
        }

        if ($response->failed()) {
            throw $this->exceptionForStatus($response);
        }

        if ($response->json('choices.0.finish_reason') === 'length') {
            throw new AiExtractionException(
                'Groq truncó la respuesta por max_tokens antes de completar el JSON.',
                502,
                'El texto es demasiado largo para procesarlo de una vez. Intenta con una descripción más corta.',
            );
        }

        $content = $response->json('choices.0.message.content');

        if (! $content) {
            throw new AiExtractionException(
                'Groq no devolvió contenido en la respuesta.',
                502,
                'La IA no devolvió una respuesta válida. Intenta de nuevo.',
            );
        }

        return new AiChatResult(
            content: $content,
            promptTokens: $response->json('usage.prompt_tokens'),
            completionTokens: $response->json('usage.completion_tokens'),
        );
    }

    private function exceptionForStatus(Response $response): AiExtractionException
    {
        $status = $response->status();

        return match (true) {
            $status === 429 => new AiExtractionException(
                "Groq rate limit (429): {$response->body()}",
                429,
                'Se alcanzó el límite de solicitudes a la IA. Espera unos segundos e intenta de nuevo.'
                .($response->header('Retry-After') ? ' (reintenta en '.$response->header('Retry-After').'s)' : ''),
            ),
            in_array($status, [401, 403], true) => new AiExtractionException(
                "Groq auth error ({$status}): {$response->body()}",
                502,
                'El autocompletado con IA no está disponible por un problema de configuración. Contacta al administrador.',
            ),
            $status >= 500 => new AiExtractionException(
                "Groq server error ({$status}): {$response->body()}",
                502,
                'El servicio de IA no está disponible en este momento. Intenta más tarde.',
            ),
            default => new AiExtractionException(
                "Groq respondió con error ({$status}): {$response->body()}",
                502,
                'No se pudo procesar la solicitud. Intenta con un texto más corto o distinto.',
            ),
        };
    }
}
