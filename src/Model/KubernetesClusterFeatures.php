<?php

namespace CleverCloud\Sdk\Model;

/**
 * Optional features enabled on a Kubernetes cluster.
 */
final readonly class KubernetesClusterFeatures
{
    /**
     * @param string|null $registries custom image registries configuration, as a free-form string
     */
    public function __construct(
        public ?bool $autoscalingEnabled = null,
        public ?bool $csi = null,
        public ?bool $nodeAutoprovisioning = null,
        public ?string $registries = null,
    ) {
    }
}
