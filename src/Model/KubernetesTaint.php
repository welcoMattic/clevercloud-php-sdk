<?php

namespace CleverCloud\Sdk\Model;

use CleverCloud\Sdk\Model\Enum\KubernetesTaintEffect;

/**
 * A Kubernetes taint applied to the nodes of a node group or to a
 * standalone node.
 */
final readonly class KubernetesTaint
{
    public function __construct(
        public string $key,
        public KubernetesTaintEffect $effect,
        public ?string $value = null,
    ) {
    }
}
