<?php

namespace CleverCloud\Sdk\Exception;

use RuntimeException;

/**
 * Wraps a PSR-18 ClientExceptionInterface (network failure, TLS, DNS, etc.).
 */
final class TransportException extends RuntimeException implements CleverCloudException
{
}
