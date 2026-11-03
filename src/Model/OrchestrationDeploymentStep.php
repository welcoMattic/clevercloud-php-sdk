<?php

namespace CleverCloud\Sdk\Model;

/**
 * One state an {@see OrchestrationDeployment} went through, with the moment
 * (RFC 3339) it entered it.
 */
final readonly class OrchestrationDeploymentStep
{
    public function __construct(
        public ?string $state = null,
        public ?string $date = null,
    ) {
    }
}
