<?php

namespace CleverCloud\Sdk\Model;

use CleverCloud\Sdk\Model\Enum\DeploymentAction;
use CleverCloud\Sdk\Model\Enum\DeploymentState;

final readonly class Deployment
{
    public function __construct(
        public string $id,
        public ?string $uuid = null,
        public ?DeploymentAction $action = null,
        public ?DeploymentState $state = null,
        public ?string $commit = null,
        public ?string $cause = null,
        public ?int $date = null,
        public ?string $author = null,
        public ?string $instanceId = null,
    ) {
    }
}
