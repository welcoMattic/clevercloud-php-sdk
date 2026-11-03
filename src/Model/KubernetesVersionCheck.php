<?php

namespace CleverCloud\Sdk\Model;

/**
 * Kubernetes versions installed on and available for a cluster.
 *
 * Versions are `MAJOR.MINOR` or `MAJOR.MINOR.PATCH` strings.
 */
final readonly class KubernetesVersionCheck
{
    /**
     * @param list<string>            $available      versions the cluster may be updated to
     * @param array<string, int>|null $workerVersions observed kubelet versions tallied by version
     *                                                (`['v1.33.4' => 3]`); only set when the check
     *                                                was asked to probe the workers and could observe them
     * @param bool|null               $workersInSync  whether every observed kubelet matches `installed`;
     *                                                `null` when unknown
     */
    public function __construct(
        public string $installed,
        public string $latest,
        public bool $needUpdate,
        public array $available = [],
        public ?array $workerVersions = null,
        public ?bool $workersInSync = null,
    ) {
    }
}
