<?php

namespace CleverCloud\Sdk;

enum ApiVersion: string
{
    case V2 = 'v2';
    case V4 = 'v4';
    case Bridge = 'bridge';

    /**
     * Unversioned paths served at the API root, such as
     * `/organisations/{id}/logs/exports` or `/networkgroup/configuration`.
     */
    case Root = 'root';
}
