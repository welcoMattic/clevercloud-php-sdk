<?php

namespace CleverCloud\Sdk\Resource\V2;

use CleverCloud\Sdk\Resource\AbstractV2Resource;

/**
 * Manages an application's environment variables. Each variable is exposed as
 * a `name => value` pair; the Clever Cloud API returns them as a list of
 * `{name, value}` objects which we flatten on read.
 */
final readonly class EnvironmentResource extends AbstractV2Resource
{
    /**
     * @return array<string, string>
     */
    public function list(string $applicationId, ?string $organisationId = null): array
    {
        /** @var list<array{name: string, value: string}> $payload */
        $payload = $this->httpGet($this->envPath($applicationId, $organisationId));

        $map = [];
        foreach ($payload as $entry) {
            if (isset($entry['name'], $entry['value']) && \is_string($entry['name']) && \is_string($entry['value'])) {
                $map[$entry['name']] = $entry['value'];
            }
        }

        return $map;
    }

    public function get(string $applicationId, string $name, ?string $organisationId = null): ?string
    {
        $variables = $this->list($applicationId, $organisationId);

        return $variables[$name] ?? null;
    }

    public function set(string $applicationId, string $name, string $value, ?string $organisationId = null): void
    {
        $this->httpPut(
            $this->envPath($applicationId, $organisationId).'/'.rawurlencode($name),
            ['json' => ['name' => $name, 'value' => $value]],
        );
    }

    /**
     * Replaces the application's environment with the given map.
     *
     * @param array<string, string> $variables
     */
    public function setMany(string $applicationId, array $variables, ?string $organisationId = null): void
    {
        $body = [];
        foreach ($variables as $name => $value) {
            $body[] = ['name' => $name, 'value' => $value];
        }

        $this->httpPut(
            $this->envPath($applicationId, $organisationId),
            ['json' => $body],
        );
    }

    public function remove(string $applicationId, string $name, ?string $organisationId = null): void
    {
        $this->httpDelete(
            $this->envPath($applicationId, $organisationId).'/'.rawurlencode($name),
        );
    }

    private function envPath(string $applicationId, ?string $organisationId): string
    {
        return $this->ownerPath($organisationId).'/applications/'.rawurlencode($applicationId).'/env';
    }
}
