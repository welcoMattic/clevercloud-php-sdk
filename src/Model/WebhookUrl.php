<?php

namespace CleverCloud\Sdk\Model;

use CleverCloud\Sdk\Model\Enum\WebhookFormat;

/**
 * One destination of a webhook: the URL called and the body format sent.
 */
final readonly class WebhookUrl
{
    public function __construct(
        public string $url,
        public ?WebhookFormat $format = null,
    ) {
    }
}
