<?php

namespace CleverCloud\Sdk\Model;

use AutoMapper\Attribute\MapFrom;

/**
 * A billing document, as returned by `/v4/billing/organisations/{id}/invoices`.
 * The API answers in snake_case; dates are ISO 8601 strings.
 */
final readonly class Invoice
{
    public function __construct(
        #[MapFrom(property: 'invoice_number')]
        public string $invoiceNumber,
        /** `PENDING`, `PAID`, ... */
        public ?string $status = null,
        /** `INVOICE`, `CREDITNOTE`, ... */
        public ?string $kind = null,
        /** `PAAS`, ... */
        public ?string $category = null,
        public ?string $currency = null,
        #[MapFrom(property: 'owner_id')]
        public ?string $ownerId = null,
        #[MapFrom(property: 'emission_date')]
        public ?string $emissionDate = null,
        #[MapFrom(property: 'pay_date')]
        public ?string $payDate = null,
        #[MapFrom(property: 'total_tax_excluded')]
        public ?InvoiceAmount $totalTaxExcluded = null,
        #[MapFrom(property: 'total_tax')]
        public ?InvoiceAmount $totalTax = null,
        #[MapFrom(property: 'vat_percent')]
        public ?float $vatPercent = null,
        public ?float $discount = null,
    ) {
    }
}
