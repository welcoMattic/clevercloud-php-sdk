<?php

namespace CleverCloud\Sdk\Resource;

use CleverCloud\Sdk\ApiVersion;

/**
 * Base class for resources backed by `api-bridge.clever-cloud.com` — the
 * gateway that hosts the new API-token-aware endpoints (token CRUD, etc.).
 */
abstract readonly class AbstractBridgeResource extends AbstractResource
{
    final protected function version(): ApiVersion
    {
        return ApiVersion::Bridge;
    }
}
