<?php

namespace CleverCloud\Sdk\Model\Enum;

enum DeploymentAction: string
{
    case Deploy = 'DEPLOY';
    case Undeploy = 'UNDEPLOY';
    case Restart = 'RESTART';
}
