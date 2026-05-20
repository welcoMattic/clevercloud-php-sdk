<?php

namespace CleverCloud\Sdk\Streaming;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Bundles the Symfony HttpClient that opened an SSE connection with the
 * response it returned, so callers can iterate the event chunks via
 * `$handle->client->stream($handle->response)`.
 */
final readonly class SseStreamHandle
{
    public function __construct(
        public HttpClientInterface $client,
        public ResponseInterface $response,
    ) {
    }
}
