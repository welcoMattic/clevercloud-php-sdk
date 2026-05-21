<?php

namespace CleverCloud\Sdk\Model\Enum;

/**
 * Status of an add-on plan migration kicked off via
 * `AddonsResource::migrate()`. Returned by `listMigrations()` /
 * `getMigration()` on the `status` field.
 */
enum MigrationStatus: string
{
    case Success = 'success';
    case InProgress = 'in-progress';
    case Pending = 'pending';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function isTerminal(): bool
    {
        return self::Success === $this || self::Failed === $this || self::Cancelled === $this;
    }
}
