<?php

namespace CleverCloud\Sdk\Model;

use AutoMapper\Attribute\MapFrom;

/**
 * A Clever Cloud organisation (a team-owned billing context).
 */
final readonly class Organisation
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description = null,
        #[MapFrom(property: 'billingEmail')]
        public ?string $billingEmail = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $zipcode = null,
        public ?string $country = null,
        public ?string $company = null,
        public ?string $vat = null,
        public ?string $avatar = null,
        #[MapFrom(property: 'vatState')]
        public ?string $vatState = null,
        #[MapFrom(property: 'customerFullName')]
        public ?string $customerFullName = null,
        #[MapFrom(property: 'canPay')]
        public ?bool $canPay = null,
        #[MapFrom(property: 'canSEPA')]
        public ?bool $canSepa = null,
        #[MapFrom(property: 'isTrusted')]
        public ?bool $isTrusted = null,
        #[MapFrom(property: 'creation_date')]
        public ?int $creationDate = null,
    ) {
    }
}
