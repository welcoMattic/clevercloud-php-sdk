<?php

namespace CleverCloud\Sdk\Model\Enum;

enum ServiceTokenStatus: string
{
    case Active = 'ACTIVE';
    case Expired = 'EXPIRED';
    case Revoked = 'REVOKED';
}
