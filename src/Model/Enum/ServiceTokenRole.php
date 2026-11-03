<?php

namespace CleverCloud\Sdk\Model\Enum;

/**
 * Role a service token holds in its organisation. Same values as
 * {@see MemberRole}, but typed separately by the API.
 */
enum ServiceTokenRole: string
{
    case Admin = 'ADMIN';
    case Manager = 'MANAGER';
    case Developer = 'DEVELOPER';
    case Accounting = 'ACCOUNTING';
}
