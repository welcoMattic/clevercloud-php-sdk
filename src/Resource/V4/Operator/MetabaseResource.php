<?php

namespace CleverCloud\Sdk\Resource\V4\Operator;

use CleverCloud\Sdk\Resource\V4\AbstractOperatorResource;

final readonly class MetabaseResource extends AbstractOperatorResource
{
    protected function operator(): string
    {
        return 'metabase';
    }
}
