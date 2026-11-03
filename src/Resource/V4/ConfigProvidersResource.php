<?php

namespace CleverCloud\Sdk\Resource\V4;

use CleverCloud\Sdk\Model\ConfigProvider;
use CleverCloud\Sdk\Resource\AbstractV4Resource;

/**
 * Reads and replaces the environment variables held by a configuration
 * provider add-on, under `/v4/addon-providers/config-provider/addons/{addonId}`.
 *
 * Variables are exposed as a `name => value` map, like
 * {@see \CleverCloud\Sdk\Resource\V2\EnvironmentResource}; the SDK converts
 * from and to the API's list of `{name, value}` objects.
 */
final readonly class ConfigProvidersResource extends AbstractV4Resource
{
    /**
     * Returns the add-on with the variables it holds.
     */
    public function get(string $addonId): ConfigProvider
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet($this->basePath($addonId));
        $payload['env'] = self::toMap($payload['env'] ?? []);

        return $this->mapTo(ConfigProvider::class, $payload);
    }

    /**
     * @return array<string, string>
     */
    public function env(string $addonId): array
    {
        return self::toMap($this->httpGet($this->basePath($addonId).'/env'));
    }

    /**
     * Replaces every variable of the add-on with `$variables`: a variable
     * missing from the map is removed. Returns the resulting variables.
     *
     * @param array<string, string> $variables
     *
     * @return array<string, string>
     */
    public function replaceEnv(string $addonId, array $variables): array
    {
        $list = [];
        foreach ($variables as $name => $value) {
            $list[] = ['name' => (string) $name, 'value' => $value];
        }

        return self::toMap($this->httpPut($this->basePath($addonId).'/env', ['json' => $list]));
    }

    private function basePath(string $addonId): string
    {
        return '/addon-providers/config-provider/addons/'.rawurlencode($addonId);
    }

    /**
     * @return array<string, string>
     */
    private static function toMap(mixed $variables): array
    {
        if (!\is_array($variables)) {
            return [];
        }

        $map = [];
        foreach ($variables as $variable) {
            if (\is_array($variable) && \is_string($variable['name'] ?? null) && \is_string($variable['value'] ?? null)) {
                $map[$variable['name']] = $variable['value'];
            }
        }

        return $map;
    }
}
