<?php

namespace CleverCloud\Sdk\Model\Enum;

/**
 * Instance size tiers exposed by Clever Cloud.
 *
 * Stable, platform-wide. Use `cases()` to populate a UI dropdown,
 * `tryFrom()` to validate untrusted input, and `->value` to send the
 * string Clever Cloud's API expects (e.g. `'nano'`, `'XL'`).
 */
enum Flavor: string
{
    case Pico = 'pico';
    case Nano = 'nano';
    case XS = 'XS';
    case S = 'S';
    case M = 'M';
    case L = 'L';
    case XL = 'XL';
    case XXL = '2XL';
    case XXXL = '3XL';
}
