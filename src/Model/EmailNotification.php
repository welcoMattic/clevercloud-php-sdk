<?php

namespace CleverCloud\Sdk\Model;

use AutoMapper\Attribute\MapFrom;

final readonly class EmailNotification
{
    /**
     * @param list<string>                  $events  event types, meta events included
     * @param list<string>                  $scope   app / add-on ids the notification is restricted to (empty = all)
     * @param list<EmailNotificationTarget> $targets recipients, read from the `notified` payload key
     */
    public function __construct(
        public string $id,
        public ?string $ownerId = null,
        public ?string $name = null,
        public array $events = [],
        public array $scope = [],
        #[MapFrom(property: 'notified')]
        public array $targets = [],
    ) {
    }
}
