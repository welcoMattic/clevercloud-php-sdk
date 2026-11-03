<?php

namespace CleverCloud\Sdk\Model;

/**
 * One side of a Pulsar namespace storage policy (retention or offload): a
 * duration limit and a size limit. A null limit means none is set.
 */
final readonly class PulsarStorageLimit
{
    public function __construct(
        public ?int $durationInMinutes = null,
        public ?int $sizeInBytes = null,
    ) {
    }
}
