<?php

namespace CleverCloud\Sdk\Model\Enum;

/**
 * How an application's source code reaches Clever Cloud.
 */
enum DeployType: string
{
    case Git = 'git';
    case Ftp = 'ftp';
    case Docker = 'docker';
}
