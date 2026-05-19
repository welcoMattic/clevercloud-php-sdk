<?php

namespace CleverCloud\Sdk\Model\Enum;

enum MemberRole: string
{
    case Admin = 'ADMIN';
    case Manager = 'MANAGER';
    case Developer = 'DEVELOPER';
    case Accounting = 'ACCOUNTING';
}
