<?php

namespace CleverCloud\Sdk\Model;

final readonly class PulsarPolicy
{
    /**
     * @param array<string, mixed> $rawPolicy
     */
    public function __construct(
        public string $addonId,
        public ?string $namespace = null,
        public ?int $retentionTimeInMinutes = null,
        public ?int $retentionSizeInMB = null,
        public ?int $messageTTLInSeconds = null,
        public array $rawPolicy = [],
    ) {
    }
}
