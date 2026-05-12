<?php

declare(strict_types=1);

namespace App\Application\Access\Sso;

final class SsoAuthenticationException extends \RuntimeException
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        private readonly string $reasonCode,
        private readonly string $userMessage,
        private readonly array $context = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($userMessage, 0, $previous);
    }

    public function getReasonCode(): string
    {
        return $this->reasonCode;
    }

    public function getUserMessage(): string
    {
        return $this->userMessage;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}

