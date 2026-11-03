<?php

namespace CleverCloud\Sdk\Resource\V2;

use CleverCloud\Sdk\Model\Backup;
use CleverCloud\Sdk\Resource\AbstractV2Resource;

/**
 * Add-on backups, read from `/v2/backups/{ownerId}/{realId}`.
 *
 * The API only lists backups: there is no single-backup read nor restore
 * route. Backup download URLs are pre-signed and short-lived; fetch any
 * backup you want to keep immediately.
 */
final readonly class BackupsResource extends AbstractV2Resource
{
    /**
     * Lists the backups of an add-on.
     *
     * `$realId` is the provider-side id (the `realId` field of the `Addon`
     * DTO, e.g. `postgresql_<uuid>`), not the `addon_<uuid>` id. The route has
     * no `/self` alias: a `null` `$organisationId` resolves the personal
     * organisation id through `GET /v2/self` first.
     *
     * @return list<Backup>
     */
    public function list(string $realId, ?string $organisationId = null): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet(
            '/backups/'.rawurlencode($this->resolveOwnerId($organisationId)).'/'.rawurlencode($realId),
        );

        return $this->mapCollection(Backup::class, $payload);
    }
}
