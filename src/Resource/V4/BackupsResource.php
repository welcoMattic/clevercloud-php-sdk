<?php

namespace CleverCloud\Sdk\Resource\V4;

use CleverCloud\Sdk\Model\Backup;
use CleverCloud\Sdk\Resource\AbstractV4Resource;

/**
 * Add-on backups exposed by Clever Cloud's V4 backups API.
 *
 * Endpoint layout:
 * `/v4/addon-providers/{providerId}/addons/{addonId}/backups`
 *
 * Backup download URLs are pre-signed and short-lived; iterate the result and
 * fetch any backup you want to keep immediately.
 */
final readonly class BackupsResource extends AbstractV4Resource
{
    /**
     * @return list<Backup>
     */
    public function list(string $providerId, string $addonId): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->backupsPath($providerId, $addonId));

        return $this->mapCollection(Backup::class, $payload);
    }

    public function get(string $providerId, string $addonId, string $backupId): Backup
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet($this->backupsPath($providerId, $addonId).'/'.rawurlencode($backupId));

        return $this->mapTo(Backup::class, $payload);
    }

    /**
     * Triggers a backup restore. The backup is identified by its id; the
     * exact restore semantics (in-place vs new instance) depend on the
     * add-on provider.
     */
    public function restore(string $providerId, string $addonId, string $backupId): void
    {
        $this->httpPost(
            $this->backupsPath($providerId, $addonId).'/'.rawurlencode($backupId).'/restore',
        );
    }

    private function backupsPath(string $providerId, string $addonId): string
    {
        return '/addon-providers/'.rawurlencode($providerId)
            .'/addons/'.rawurlencode($addonId).'/backups';
    }
}
