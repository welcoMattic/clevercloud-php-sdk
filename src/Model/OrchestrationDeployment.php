<?php

namespace CleverCloud\Sdk\Model;

/**
 * A deployment as the V4 orchestration API reports it: one run of the
 * orchestrator bringing an application to a new state (deploy, undeploy,
 * scaling). Its shape differs from the V2 {@see Deployment}.
 *
 * Dates are RFC 3339 strings.
 */
final readonly class OrchestrationDeployment
{
    /**
     * @param string|null                       $state   state of the most recent step: `QUEUED`, `WORK_IN_PROGRESS`, `TASK_IN_PROGRESS`, `FAILED`, `CANCELLED` or `SUCCEEDED`
     * @param list<OrchestrationDeploymentStep> $steps   states the deployment went through, oldest first
     * @param array<string, mixed>              $version `commitId` and `previousCommitId`
     * @param array<string, mixed>              $origin  `action` (`DEPLOY`, `UNDEPLOY`, `UPSCALE`, `DOWNSCALE`, ...), `cause`, `source`, `authorId`, `constraints`, `priority`
     */
    public function __construct(
        public string $id,
        public ?string $ownerId = null,
        public ?string $applicationId = null,
        public ?string $startDate = null,
        public ?string $state = null,
        public array $steps = [],
        public array $version = [],
        public array $origin = [],
        public ?bool $hasDedicatedBuild = null,
    ) {
    }
}
