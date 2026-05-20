<?php

namespace CleverCloud\Sdk\Model;

use CleverCloud\Sdk\Model\Enum\WebhookFormat;

final readonly class Webhook
{
    /**
     * @param list<string> $events
     * @param list<string> $scope
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $url,
        public ?WebhookFormat $format = null,
        public array $events = [],
        public array $scope = [],
    ) {
    }
}
