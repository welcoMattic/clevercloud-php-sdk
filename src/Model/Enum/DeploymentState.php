<?php

namespace CleverCloud\Sdk\Model\Enum;

/**
 * Lifecycle state of a deployment as reported by `/v2/.../deployments`.
 */
enum DeploymentState: string
{
    case Ok = 'OK';
    case Fail = 'FAIL';
    case Cancelled = 'CANCELLED';
    case Wip = 'WIP';
    case Queued = 'QUEUED';
}
