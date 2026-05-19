<?php

namespace CleverCloud\Sdk\Model;

final readonly class Invoice
{
    public function __construct(
        public string $invoiceNumber,
        public ?string $status = null,
        public ?string $type = null,
        public ?int $emissionDate = null,
        public ?int $payDate = null,
        public ?string $totalTax = null,
        public ?string $totalTaxExcluded = null,
        public ?string $totalTaxIncluded = null,
        public ?string $currency = null,
        public ?string $kind = null,
        public ?string $downloadUrl = null,
    ) {
    }
}
