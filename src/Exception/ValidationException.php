<?php

namespace CleverCloud\Sdk\Exception;

use Throwable;

/**
 * Raised on HTTP 400 / 422 when the API reports validation errors.
 */
final class ValidationException extends ApiException
{
    /**
     * @param array<string, list<string>> $errors field name => list of error messages
     * @param array<string, mixed>        $body
     */
    public function __construct(
        string $message,
        public readonly array $errors,
        int $statusCode,
        ?string $errorCode = null,
        ?string $requestId = null,
        array $body = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $errorCode, $requestId, $body, $previous);
    }
}
