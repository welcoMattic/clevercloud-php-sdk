<?php

namespace CleverCloud\Sdk\Model\Enum;

enum DrainType: string
{
    case Datadog = 'DatadogHTTP';
    case ElasticSearch = 'ElasticSearch';
    case NewRelic = 'NewRelicHTTP';
    case OvhTcp = 'TCPSyslog';
    case RawHttp = 'HTTP';
    case SyslogTcp = 'SyslogTCP';
    case SyslogUdp = 'SyslogUDP';
}
