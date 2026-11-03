<?php

namespace CleverCloud\Sdk\Model;

final readonly class Webhook
{
    /**
     * @param list<WebhookUrl> $urls
     * @param list<string>     $events
     * @param list<string>     $scope
     */
    public function __construct(
        public string $id,
        public ?string $ownerId = null,
        public ?string $name = null,
        public array $urls = [],
        public array $events = [],
        public array $scope = [],
        public ?string $state = null,
    ) {
    }
}
