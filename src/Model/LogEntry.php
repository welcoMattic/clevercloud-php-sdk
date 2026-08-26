<?php

namespace CleverCloud\Sdk\Model;

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
        public ?string $id = null,
        public ?string $severity = null,
        public ?int $priority = null,
        public ?string $date = null,
        public ?string $instanceId = null,
        public ?string $applicationId = null,
        public ?string $deploymentId = null,
        public ?string $commitId = null,
        public ?string $service = null,
        public ?string $region = null,
        public ?string $zone = null,
        public ?string $version = null,
        public array $raw = [],
    ) {
    }
}
