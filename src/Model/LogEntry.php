<?php

namespace CleverCloud\Sdk\Model;

use AutoMapper\Attribute\MapFrom;

/**
 * A single log line emitted by an application instance.
 */
final readonly class LogEntry
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public string $message,
        public ?string $instanceId = null,
        public ?string $applicationId = null,
        public ?string $stream = null,
        public ?string $severity = null,
        public ?string $zone = null,
        public ?string $deploymentId = null,
        #[MapFrom(property: 'date')]
        public ?string $date = null,
        public array $raw = [],
    ) {
    }
}
