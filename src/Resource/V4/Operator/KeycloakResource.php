<?php

namespace CleverCloud\Sdk\Resource\V4\Operator;

use CleverCloud\Sdk\Model\OperatorVersionCheck;
use CleverCloud\Sdk\Resource\V4\AbstractOperatorResource;

final readonly class KeycloakResource extends AbstractOperatorResource
{
    /**
     * Tells which Keycloak versions are on offer and whether the add-on runs
     * the latest one.
     */
    public function checkVersion(string $id): OperatorVersionCheck
    {
        return $this->fetchVersionCheck($id);
    }

    /**
     * Switches the add-on to `$targetVersion` (one of the versions listed by
     * {@see checkVersion()}). Returns the updated add-on as the Keycloak
     * provider describes it.
     *
     * @return array<string, mixed>
     */
    public function updateVersion(string $id, string $targetVersion): array
    {
        return $this->requestVersionUpdate($id, $targetVersion);
    }

    protected function operator(): string
    {
        return 'keycloak';
    }

    protected function providerInformationPath(): string
    {
        return '/addon-providers/keycloak';
    }
}
