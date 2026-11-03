<?php

namespace CleverCloud\Sdk\Model;

/**
 * A topic of a Pulsar add-on namespace.
 */
final readonly class PulsarTopic
{
    /**
     * @param string $name full topic name, e.g. `persistent://tenant/ns/topic`
     */
    public function __construct(
        public string $name,
    ) {
    }
}
