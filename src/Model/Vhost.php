<?php

namespace CleverCloud\Sdk\Model;

/**
 * A virtual host bound to an application (a fully-qualified domain name).
 */
final readonly class Vhost
{
    public function __construct(public string $fqdn)
    {
    }
}
