<?php

namespace CleverCloud\Sdk\Resource\V4;

use CleverCloud\Sdk\Model\AddonProviderInformation;
use CleverCloud\Sdk\Model\MateriaKvDatabase;
use CleverCloud\Sdk\Resource\AbstractV4Resource;

/**
 * Reads Materia KV databases and the Materia KV provider.
 */
final readonly class MateriaKvResource extends AbstractV4Resource
{
    /**
     * Returns the Materia KV provider: the cluster add-ons are created on.
     * The endpoint requires no authentication.
     */
    public function providerInformation(): AddonProviderInformation
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet('/addon-providers/kv');

        return $this->mapTo(AddonProviderInformation::class, $payload);
    }

    /**
     * Returns a Materia KV database, including its connection host, port and
     * access token.
     *
     * @param string $ownerId owner of the add-on: an organisation (`orga_...`) or a user (`user_...`)
     * @param string $kvId    the real add-on id (`kv_...`)
     */
    public function get(string $ownerId, string $kvId): MateriaKvDatabase
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet(
            '/materia/organisations/'.rawurlencode($ownerId).'/materia/databases/'.rawurlencode($kvId),
        );

        return $this->mapTo(MateriaKvDatabase::class, $payload);
    }
}
