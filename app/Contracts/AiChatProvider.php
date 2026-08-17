<?php

namespace App\Contracts;

use App\Exceptions\AiExtractionException;
use App\Services\Ai\AiChatResult;

/**
 * Abstrae la llamada a un proveedor de LLM (Groq, OpenAI, Gemini, Anthropic...)
 * para que los servicios de la app dependan de este contrato y no del proveedor
 * concreto. Cambiar de proveedor implica escribir una nueva implementación y
 * rebindearla en el service provider, sin tocar el resto de la app.
 */
interface AiChatProvider
{
    /**
     * Identificador corto del proveedor, usado solo para logging (p. ej. "groq").
     */
    public function name(): string;

    /**
     * Envía el prompt de sistema y el texto del usuario, y devuelve la
     * respuesta ya extraída (texto + tokens consumidos, si el proveedor los reporta).
     *
     * @param  array<string, mixed>|null  $jsonSchema  JSON Schema (estilo OpenAI/Anthropic
     *                                                  structured outputs) que debe cumplir
     *                                                  la respuesta, o null para JSON libre.
     *
     * @throws AiExtractionException si el proveedor falla (HTTP, timeout, etc.)
     */
    public function complete(string $systemPrompt, string $userText, ?array $jsonSchema = null): AiChatResult;
}
