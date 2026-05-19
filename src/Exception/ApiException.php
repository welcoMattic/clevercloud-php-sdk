<?php

namespace CleverCloud\Sdk\Exception;

use RuntimeException;
use Throwable;

/**
 * Base for every error returned by the Clever Cloud API. Concrete on its own so
 * it can be raised for any 4xx that doesn't match a more specific subclass.
 */
class ApiException extends RuntimeException implements CleverCloudException
{
    /**
     * @param array<string, mixed> $body decoded JSON body, or empty array when none
     */
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly ?string $errorCode = null,
        public readonly ?string $requestId = null,
        public readonly array $body = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }
}
