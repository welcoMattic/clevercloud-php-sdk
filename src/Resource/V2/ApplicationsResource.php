<?php

namespace CleverCloud\Sdk\Resource\V2;

use CleverCloud\Sdk\Model\Application;
use CleverCloud\Sdk\Resource\AbstractV2Resource;

final readonly class ApplicationsResource extends AbstractV2Resource
{
    /**
     * @return list<Application>
     */
    public function list(?string $organisationId = null): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->ownerPath($organisationId).'/applications');

        return $this->mapCollection(Application::class, $payload);
    }

    public function get(string $applicationId, ?string $organisationId = null): Application
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet($this->appPath($applicationId, $organisationId));

        return $this->mapTo(Application::class, $payload);
    }

    /**
     * @param array<string, mixed> $data minimal shape: {name, deploy: 'git'|'ftp', instanceType, instanceVariant, zone}
     */
    public function create(array $data, ?string $organisationId = null): Application
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost(
            $this->ownerPath($organisationId).'/applications',
            ['json' => $data],
        );

        return $this->mapTo(Application::class, $payload);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $applicationId, array $data, ?string $organisationId = null): Application
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPut(
            $this->appPath($applicationId, $organisationId),
            ['json' => $data],
        );

        return $this->mapTo(Application::class, $payload);
    }

    public function delete(string $applicationId, ?string $organisationId = null): void
    {
        $this->httpDelete($this->appPath($applicationId, $organisationId));
    }

    public function restart(string $applicationId, ?string $organisationId = null, bool $withoutCache = false): void
    {
        $query = $withoutCache ? ['useCache' => 'no'] : [];
        $this->httpPost(
            $this->appPath($applicationId, $organisationId).'/instances',
            ['query' => $query],
        );
    }

    public function stop(string $applicationId, ?string $organisationId = null): void
    {
        $this->httpDelete($this->appPath($applicationId, $organisationId).'/instances');
    }

    public function setBranch(string $applicationId, string $branch, ?string $organisationId = null): void
    {
        $this->httpPut(
            $this->appPath($applicationId, $organisationId).'/branch',
            ['json' => ['branch' => $branch]],
        );
    }

    /**
     * Lists currently running instances for an application.
     *
     * @return list<array<string, mixed>>
     */
    public function instances(string $applicationId, ?string $organisationId = null): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->appPath($applicationId, $organisationId).'/instances');

        return $payload;
    }

    // ------------------------------------------------------------------
    //  Dependencies (env shared between linked apps)
    // ------------------------------------------------------------------

    /**
     * @return list<array<string, mixed>> raw dependency app payloads
     */
    public function dependencies(string $applicationId, ?string $organisationId = null): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->appPath($applicationId, $organisationId).'/dependencies');

        return $payload;
    }

    /**
     * Links another application as a dependency (its exposed env will be merged in).
     */
    public function addDependency(string $applicationId, string $dependencyAppId, ?string $organisationId = null): void
    {
        $this->httpPut(
            $this->appPath($applicationId, $organisationId).'/dependencies/'.rawurlencode($dependencyAppId),
        );
    }

    public function removeDependency(string $applicationId, string $dependencyAppId, ?string $organisationId = null): void
    {
        $this->httpDelete(
            $this->appPath($applicationId, $organisationId).'/dependencies/'.rawurlencode($dependencyAppId),
        );
    }

    // ------------------------------------------------------------------
    //  Tags
    // ------------------------------------------------------------------

    /**
     * @return list<string>
     */
    public function tags(string $applicationId, ?string $organisationId = null): array
    {
        /** @var list<string> $payload */
        $payload = $this->httpGet($this->appPath($applicationId, $organisationId).'/tags');

        return $payload;
    }

    public function addTag(string $applicationId, string $tag, ?string $organisationId = null): void
    {
        $this->httpPut(
            $this->appPath($applicationId, $organisationId).'/tags/'.rawurlencode($tag),
        );
    }

    public function removeTag(string $applicationId, string $tag, ?string $organisationId = null): void
    {
        $this->httpDelete(
            $this->appPath($applicationId, $organisationId).'/tags/'.rawurlencode($tag),
        );
    }

    // ------------------------------------------------------------------
    //  Exposed env (env vars exposed to apps that depend on this one)
    // ------------------------------------------------------------------

    /**
     * @return array<string, string>
     */
    public function exposedEnv(string $applicationId, ?string $organisationId = null): array
    {
        /** @var list<array{name: string, value: string}> $payload */
        $payload = $this->httpGet($this->appPath($applicationId, $organisationId).'/exposed_env');

        $map = [];
        foreach ($payload as $entry) {
            if (isset($entry['name'], $entry['value']) && \is_string($entry['name']) && \is_string($entry['value'])) {
                $map[$entry['name']] = $entry['value'];
            }
        }

        return $map;
    }

    /**
     * Replaces the application's exposed env block.
     *
     * @param array<string, string> $variables
     */
    public function setExposedEnv(string $applicationId, array $variables, ?string $organisationId = null): void
    {
        $body = [];
        foreach ($variables as $name => $value) {
            $body[] = ['name' => $name, 'value' => $value];
        }

        $this->httpPut(
            $this->appPath($applicationId, $organisationId).'/exposed_env',
            ['json' => $body],
        );
    }

    // ------------------------------------------------------------------
    //  Linked add-ons
    // ------------------------------------------------------------------

    /**
     * Add-ons linked to this application (their env vars get merged into the app's env).
     *
     * @return list<array<string, mixed>>
     */
    public function addons(string $applicationId, ?string $organisationId = null): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->appPath($applicationId, $organisationId).'/addons');

        return $payload;
    }

    public function linkAddon(string $applicationId, string $addonId, ?string $organisationId = null): void
    {
        $this->httpPost(
            $this->appPath($applicationId, $organisationId).'/addons',
            ['json' => ['addon_id' => $addonId]],
        );
    }

    public function unlinkAddon(string $applicationId, string $addonId, ?string $organisationId = null): void
    {
        $this->httpDelete(
            $this->appPath($applicationId, $organisationId).'/addons/'.rawurlencode($addonId),
        );
    }

    private function appPath(string $applicationId, ?string $organisationId): string
    {
        return $this->ownerPath($organisationId).'/applications/'.rawurlencode($applicationId);
    }
}
