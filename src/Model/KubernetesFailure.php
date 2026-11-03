<?php

namespace CleverCloud\Sdk\Model;

/**
 * Why a Kubernetes cluster or node group operation failed.
 */
final readonly class KubernetesFailure
{
    /**
     * @param string                     $occurredAt     RFC 3339 date-time
     * @param string|null                $step           absent when the operation has no steps
     * @param list<array<string, mixed>> $undrainedNodes node group failures only: the nodes that could
     *                                                   not be drained, each tagged by `outcome`
     */
    public function __construct(
        public string $operation,
        public string $message,
        public string $occurredAt,
        public ?string $step = null,
        public array $undrainedNodes = [],
    ) {
    }
}
