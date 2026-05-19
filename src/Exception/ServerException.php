<?php

namespace CleverCloud\Sdk\Exception;

/**
 * Raised on HTTP 5xx after the retry policy has been exhausted.
 */
final class ServerException extends ApiException
{
}
