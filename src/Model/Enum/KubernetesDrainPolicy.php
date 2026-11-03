<?php

namespace CleverCloud\Sdk\Model\Enum;

/**
 * What an operation does with a node whose pods will not leave when it is
 * drained. `STRICT` (the API default) never deletes a node that did not drain:
 * the operation fails instead. `FORCE` drains under a shorter deadline, then
 * deletes the node regardless.
 */
enum KubernetesDrainPolicy: string
{
    case Strict = 'STRICT';
    case Force = 'FORCE';
}
