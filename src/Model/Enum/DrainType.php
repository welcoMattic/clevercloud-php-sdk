<?php

namespace CleverCloud\Sdk\Model\Enum;

/**
 * Protocol a log drain ships its logs with: the `recipient.type` of a V4
 * drain.
 */
enum DrainType: string
{
    case BetterStack = 'BETTERSTACK';
    case Datadog = 'DATADOG';
    case ElasticSearch = 'ELASTICSEARCH';
    case NewRelic = 'NEWRELIC';
    case OvhTcp = 'OVH_TCP';
    case RawHttp = 'RAW_HTTP';
    case Splunk = 'SPLUNK';
    case SyslogTcp = 'SYSLOG_TCP';
    case SyslogUdp = 'SYSLOG_UDP';
}
