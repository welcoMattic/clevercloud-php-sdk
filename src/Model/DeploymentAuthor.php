<?php

namespace CleverCloud\Sdk\Model;

/**
 * Identifies the user who triggered a deployment.
 */
final readonly class DeploymentAuthor
{
    public function __construct(
        public ?string $id = null,
        public ?string $name = null,
    ) {
    }
}
