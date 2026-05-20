<?php

namespace CleverCloud\Sdk\Model;

final readonly class EmailNotification
{
    /**
     * @param list<string> $events  event types ('*' for all, or specific ones)
     * @param list<string> $scope   service / app / addon ids the notification is restricted to (empty = all)
     * @param list<string> $targets recipients: email addresses, user ids, or organisation ids
     */
    public function __construct(
        public string $id,
        public string $name,
        public array $events = [],
        public array $scope = [],
        public array $targets = [],
    ) {
    }
}
