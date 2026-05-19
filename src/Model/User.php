<?php

namespace CleverCloud\Sdk\Model;

use AutoMapper\Attribute\MapFrom;

/**
 * A Clever Cloud user account, as returned by `/v2/self` and `/v2/users/{id}`.
 *
 * Only a stable subset of fields is modelled — anything not listed here is
 * available in the raw API response should you need it.
 */
final readonly class User
{
    public function __construct(
        public string $id,
        public ?string $email = null,
        public ?string $firstname = null,
        public ?string $lastname = null,
        public ?string $name = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $zipcode = null,
        public ?string $country = null,
        public ?string $avatar = null,
        public ?string $lang = null,
        #[MapFrom(property: 'preferred_mfa')]
        public ?string $preferredMfa = null,
        #[MapFrom(property: 'has_password')]
        public ?bool $hasPassword = null,
        #[MapFrom(property: 'can_pay')]
        public ?bool $canPay = null,
        #[MapFrom(property: 'email_validated')]
        public ?bool $emailValidated = null,
        #[MapFrom(property: 'creation_date')]
        public ?int $creationDate = null,
    ) {
    }
}
