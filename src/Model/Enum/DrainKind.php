<?php

namespace CleverCloud\Sdk\Model\Enum;

/**
 * Stream of logs a drain attached to an application or an add-on ships.
 */
enum DrainKind: string
{
    case Log = 'LOG';
    case AccessLog = 'ACCESSLOG';
}
