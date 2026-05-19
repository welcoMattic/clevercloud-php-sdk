<?php

namespace CleverCloud\Sdk\Exception;

use RuntimeException;

/**
 * Wraps a JSON encode/decode failure.
 */
final class JsonException extends RuntimeException implements CleverCloudException
{
}
