<?php

namespace CleverCloud\Sdk\Exception;

use Throwable;

/**
 * Raised on HTTP 429 after the retry policy has been exhausted.
 */
final class RateLimitException extends ApiException
{
    /**
     * @param array<string, mixed> $body
     */
    public function __construct(
        string $message,
        public readonly ?int $retryAfterSeconds,
        int $statusCode = 429,
        ?string $errorCode = null,
        ?string $requestId = null,
        array $body = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $errorCode, $requestId, $body, $previous);
    }
}
