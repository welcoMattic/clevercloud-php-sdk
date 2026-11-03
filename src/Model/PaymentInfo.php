<?php

namespace CleverCloud\Sdk\Model;

/**
 * Billing details of a user or an organisation, as returned by
 * `/v2/self/payment-info` and `/v2/organisations/{id}/payment-info`.
 *
 * `errors` lists the problems that prevent the details from being used for
 * payment, keyed by field name (such as `zipCode`); it is empty when the
 * details are complete and valid.
 */
final readonly class PaymentInfo
{
    /**
     * @param array<string, string> $errors
     */
    public function __construct(
        public bool $fromOrga = false,
        public array $errors = [],
        public ?string $name = null,
        public ?string $company = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $zipCode = null,
        public ?string $country = null,
        public ?string $vatNumber = null,
    ) {
    }
}
