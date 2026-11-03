<?php

namespace CleverCloud\Sdk\Model;

use AutoMapper\Attribute\MapFrom;

/**
 * An amount on an {@see Invoice}: `amount` is numeric, the two other fields
 * are ready-to-display strings (`38.82`, `€38.82`).
 */
final readonly class InvoiceAmount
{
    public function __construct(
        public float $amount,
        public ?string $currency = null,
        #[MapFrom(property: 'amount_formatted')]
        public ?string $amountFormatted = null,
        #[MapFrom(property: 'default_display')]
        public ?string $defaultDisplay = null,
    ) {
    }
}
