<?php

namespace CleverCloud\Sdk\Model;

use AutoMapper\Attribute\MapFrom;

/**
 * A Clever Cloud user account, as returned by `/v2/self`.
 *
 * Only a stable subset of fields is modelled: anything not listed here is
 * available in the raw API response should you need it.
 *
 * The payload uses camelCase, so most properties map by name alone. The one
 * exception is `preferredMFA`, whose casing does not match the property, hence
 * the explicit attribute.
 */
final readonly class User
{
    public function __construct(
        public string $id,
        public ?string $email = null,
        /**
         * Full display name. The API exposes a single `name` field and no
         * separate given/family name, so do not expect to split it reliably.
         */
        public ?string $name = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $zipcode = null,
        public ?string $country = null,
        public ?string $avatar = null,
        public ?string $lang = null,
        #[MapFrom(property: 'preferredMFA')]
        public ?string $preferredMfa = null,
        public ?bool $hasPassword = null,
        public ?bool $canPay = null,
        public ?bool $emailValidated = null,
        public ?int $creationDate = null,
    ) {
    }
}
