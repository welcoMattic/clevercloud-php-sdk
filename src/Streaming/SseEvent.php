<?php

namespace CleverCloud\Sdk\Streaming;

/**
 * One frame parsed off a Server-Sent Events stream.
 */
final readonly class SseEvent
{
    public function __construct(
        public string $data,
        public ?string $event = null,
        public ?string $id = null,
        public ?int $retry = null,
    ) {
    }
}
