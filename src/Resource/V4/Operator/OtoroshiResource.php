<?php

namespace CleverCloud\Sdk\Resource\V4\Operator;

use CleverCloud\Sdk\Model\OperatorVersionCheck;
use CleverCloud\Sdk\Resource\V4\AbstractOperatorResource;

final readonly class OtoroshiResource extends AbstractOperatorResource
{
    /**
     * Tells which Otoroshi versions are on offer and whether the add-on runs
     * the latest one.
     */
    public function checkVersion(string $id): OperatorVersionCheck
    {
        return $this->fetchVersionCheck($id);
    }

    /**
     * Switches the add-on to `$targetVersion` (one of the versions listed by
     * {@see checkVersion()}). Returns the updated add-on as the Otoroshi
     * provider describes it.
     *
     * @return array<string, mixed>
     */
    public function updateVersion(string $id, string $targetVersion): array
    {
        return $this->requestVersionUpdate($id, $targetVersion);
    }

    /**
     * Returns the Otoroshi `config.yaml` of the add-on as a raw YAML string.
     */
    public function configYaml(string $id): string
    {
        $response = $this->httpStream(
            'GET',
            $this->addonPath($id).'/config.yaml',
            ['headers' => ['Accept' => 'application/yaml']],
        );

        return (string) $response->getBody();
    }

    /**
     * Links the add-on to a Network Group.
     */
    public function linkNetworkGroup(string $id): void
    {
        $this->attachNetworkGroup($id);
    }

    /**
     * Unlinks the add-on from its Network Group.
     */
    public function unlinkNetworkGroup(string $id): void
    {
        $this->detachNetworkGroup($id);
    }

    protected function operator(): string
    {
        return 'otoroshi';
    }

    protected function providerInformationPath(): string
    {
        return '/addon-providers/otoroshi';
    }
}
