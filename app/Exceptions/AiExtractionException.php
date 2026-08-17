<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Separa el detalle interno (para logs) del mensaje seguro que puede
 * mostrarse al usuario, para no relayar respuestas crudas del proveedor de IA.
 */
class AiExtractionException extends RuntimeException
{
    public function __construct(
        string $internalMessage,
        private readonly int $httpStatus,
        private readonly string $userMessage,
        ?Throwable $previous = null,
    ) {
        parent::__construct($internalMessage, 0, $previous);
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }

    public function userMessage(): string
    {
        return $this->userMessage;
    }
}
