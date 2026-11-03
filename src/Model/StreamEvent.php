<?php

namespace CleverCloud\Sdk\Model;

/**
 * One Server-Sent Event received on a Clever Cloud stream whose payload the
 * API does not document, such as access logs (`ACCESS_LOG` events) or
 * platform events (`/v2/events`).
 *
 * The OpenAPI spec publishes no schema for these payloads, so the SDK keeps
 * the decoded JSON as is instead of guessing a typed shape.
 */
final readonly class StreamEvent
{
    /**
     * @param string               $type the SSE `event` field, `message` when the server omits it
     * @param array<string, mixed> $data the JSON-decoded SSE `data` field
     * @param string|null          $id   the SSE `id` field, used to resume with `Last-Event-ID`
     */
    public function __construct(
        public string $type,
        public array $data,
        public ?string $id = null,
    ) {
    }
}
