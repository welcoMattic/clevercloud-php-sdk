<?php

namespace CleverCloud\Sdk\Resource;

use CleverCloud\Sdk\ApiVersion;

abstract readonly class AbstractV2Resource extends AbstractResource
{
    final protected function version(): ApiVersion
    {
        return ApiVersion::V2;
    }
}
