<?php

namespace CleverCloud\Sdk\Model;

/**
 * A Clever Cloud namespace (e.g. `default`, `cleverapps`, `cleverapps.cc`).
 *
 * Class name suffixed because `namespace` is a PHP reserved word.
 */
final readonly class Namespace_
{
    public function __construct(public string $name)
    {
    }
}
