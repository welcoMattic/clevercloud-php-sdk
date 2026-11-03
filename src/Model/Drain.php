<?php

namespace CleverCloud\Sdk\Model;

/**
 * A log drain: forwards application or add-on logs to an external sink
 * (Datadog, ElasticSearch, NewRelic, syslog, raw HTTP, etc.).
 */
final readonly class Drain
{
    /**
     * @param string|null          $kind      stream shipped: `LOG` or `ACCESSLOG`
     * @param array<string, mixed> $status    `status` (`CREATED`, `ENABLING`, `ENABLED`, `DISABLING`, `DISABLED`, `DELETED`), `date`, and `authorId` / `errorReason` when set
     * @param array<string, mixed> $recipient `type` (a {@see Enum\DrainType} value), `url` and the protocol fields (`username`, `password`, `apiKey`, `index`, `token`, ...)
     * @param array<string, mixed> $execution `status` (`RUNNING`, `RETRYING`, `NOT_RUNNING`) and the retry details of the shipping worker
     * @param array<string, mixed> $backlog   `msgRateOut`, `msgThroughputOut` and `msgBacklog` when reported
     */
    public function __construct(
        public string $id,
        public ?string $resourceId = null,
        public ?string $kind = null,
        public array $status = [],
        public array $recipient = [],
        public array $execution = [],
        public array $backlog = [],
    ) {
    }
}
