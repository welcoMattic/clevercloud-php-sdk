<?php

namespace CleverCloud\Sdk\Resource\V4\Operator;

use CleverCloud\Sdk\Resource\V4\AbstractOperatorResource;

final readonly class MatomoResource extends AbstractOperatorResource
{
    protected function operator(): string
    {
        return 'matomo';
    }
}
