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
        #[MapFrom(property: 'instance_id')]
        public ?string $instanceId = null,
        #[MapFrom(property: 'application_id')]
        public ?string $applicationId = null,
        public ?string $stream = null,
        public ?string $severity = null,
        public ?string $zone = null,
        #[MapFrom(property: 'deployment_id')]
        public ?string $deploymentId = null,
        public ?string $date = null,
        public array $raw = [],
    ) {
    }
}
