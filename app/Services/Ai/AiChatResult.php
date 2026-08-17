<?php

namespace App\Services\Ai;

final class AiChatResult
{
    public function __construct(
        public readonly string $content,
        public readonly ?int $promptTokens = null,
        public readonly ?int $completionTokens = null,
    ) {}
}
