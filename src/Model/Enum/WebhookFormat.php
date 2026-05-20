<?php

namespace CleverCloud\Sdk\Model\Enum;

enum WebhookFormat: string
{
    case Raw = 'raw';
    case Slack = 'slack';
    case Gitter = 'gitter';
    case Flowdock = 'flowdock';
}
