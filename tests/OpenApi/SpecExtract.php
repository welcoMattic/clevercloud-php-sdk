<?php

namespace CleverCloud\Sdk\Tests\OpenApi;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

use RuntimeException;

/**
 * Compact, committed extract of the Clever Cloud OpenAPI document.
 *
 * Keeps what the drift check and the model contract test need (operations,
 * schema property names and types) and drops everything else, prose included.
 * The pin (source URL, sha256 of the raw document, fetch date) lets the weekly
 * workflow tell when the published document moved.
 *
 * @phpstan-type Operation array{method: string, path: string, operationId: string, tags: list<string>, security: list<string>}
 * @phpstan-type Schema array{type: string, properties: array<string, string>}
 */
final readonly class SpecExtract
{
    public const string SOURCE_URL = 'https://api.clever-cloud.com/openapi.json';

    private const array HTTP_METHODS = ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace'];

    /**
     * @param list<Operation>       $operations
     * @param array<string, Schema> $schemas
     */
    public function __construct(
        public string $source,
        public string $sha256,
        public string $fetchedAt,
        public array $operations,
        public array $schemas,
    ) {
    }

    public static function defaultPath(): string
    {
        return \dirname(__DIR__).'/Fixtures/openapi/extract.json';
    }

    public static function load(?string $path = null): self
    {
        $path ??= self::defaultPath();
        $json = file_get_contents($path);
        if (false === $json) {
            throw new RuntimeException(\sprintf('Cannot read the OpenAPI extract at %s.', $path));
        }

        /** @var array{source: string, sha256: string, fetchedAt: string, operations: list<Operation>, schemas: array<string, Schema>} $data */
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return new self($data['source'], $data['sha256'], $data['fetchedAt'], $data['operations'], $data['schemas']);
    }

    /**
     * Builds the extract from the raw bytes of the published document.
     */
    public static function fromRawSpec(string $raw, string $fetchedAt, string $source = self::SOURCE_URL): self
    {
        /** @var array<string, mixed> $spec */
        $spec = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        $schemas = [];
        $components = self::map(self::map($spec['components'] ?? [])['schemas'] ?? []);
        foreach ($components as $name => $schema) {
            $schemas[$name] = [
                'type' => self::typeOf(self::map($schema)),
                'properties' => self::propertiesOf(self::map($schema), $components),
            ];
        }
        ksort($schemas);

        return new self($source, hash('sha256', $raw), $fetchedAt, self::operationsOf($spec), $schemas);
    }

    /**
     * Pretty-printed JSON with one operation per line, so a refresh diffs
     * cleanly operation by operation and property by property.
     */
    public function toJson(): string
    {
        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;
        $operations = array_map(
            static fn (array $operation): string => '        '.json_encode($operation, $flags),
            $this->operations,
        );
        $schemas = str_replace("\n", "\n    ", json_encode($this->schemas, $flags | JSON_PRETTY_PRINT));

        return "{\n"
            .'    "source": '.json_encode($this->source, $flags).",\n"
            .'    "sha256": '.json_encode($this->sha256, $flags).",\n"
            .'    "fetchedAt": '.json_encode($this->fetchedAt, $flags).",\n"
            ."    \"operations\": [\n".implode(",\n", $operations)."\n    ],\n"
            .'    "schemas": '.$schemas."\n"
            ."}\n";
    }

    /**
     * @param array<string, mixed> $spec
     *
     * @return list<Operation>
     */
    private static function operationsOf(array $spec): array
    {
        $defaultSecurity = $spec['security'] ?? null;
        $operations = [];
        foreach (self::map($spec['paths'] ?? []) as $path => $item) {
            foreach (self::map($item) as $method => $operation) {
                if (!\in_array($method, self::HTTP_METHODS, true)) {
                    continue;
                }
                $operation = self::map($operation);
                $operationId = $operation['operationId'] ?? '';
                $operations[] = [
                    'method' => strtoupper($method),
                    'path' => $path,
                    'operationId' => \is_string($operationId) ? $operationId : '',
                    'tags' => self::strings($operation['tags'] ?? []),
                    'security' => self::security($operation['security'] ?? $defaultSecurity),
                ];
            }
        }
        usort($operations, static fn (array $a, array $b): int => [$a['path'], $a['method']] <=> [$b['path'], $b['method']]);

        return $operations;
    }

    /**
     * One entry per accepted alternative, schemes of the same alternative
     * joined with `+`. An empty list means the operation is public.
     *
     * @return list<string>
     */
    private static function security(mixed $requirements): array
    {
        $alternatives = [];
        foreach (\is_array($requirements) ? $requirements : [] as $requirement) {
            $schemes = array_map('strval', array_keys(self::map($requirement)));
            if ([] !== $schemes) {
                $alternatives[] = implode('+', $schemes);
            }
        }

        return $alternatives;
    }

    /**
     * Property names and compact types of a schema. `allOf` parts are merged,
     * `oneOf` / `anyOf` variants are unioned (a model may legitimately read a
     * field that only one variant carries).
     *
     * @param array<string, mixed> $schema
     * @param array<string, mixed> $components
     * @param list<string>         $seen
     *
     * @return array<string, string>
     */
    private static function propertiesOf(array $schema, array $components, array $seen = []): array
    {
        if (isset($schema['$ref']) && \is_string($schema['$ref'])) {
            $name = self::refName($schema['$ref']);
            if (\in_array($name, $seen, true)) {
                return [];
            }

            return self::propertiesOf(self::map($components[$name] ?? []), $components, [...$seen, $name]);
        }

        $properties = [];
        foreach (self::map($schema['properties'] ?? []) as $name => $property) {
            $properties[$name] = self::typeOf(self::map($property));
        }
        foreach (['allOf', 'oneOf', 'anyOf'] as $keyword) {
            foreach (\is_array($schema[$keyword] ?? null) ? $schema[$keyword] : [] as $part) {
                $properties += self::propertiesOf(self::map($part), $components, $seen);
            }
        }
        ksort($properties);

        return $properties;
    }

    /**
     * @param array<string, mixed> $schema
     */
    private static function typeOf(array $schema): string
    {
        if (isset($schema['$ref']) && \is_string($schema['$ref'])) {
            return '#'.self::refName($schema['$ref']);
        }
        foreach (['oneOf' => '|', 'anyOf' => '|', 'allOf' => '&'] as $keyword => $glue) {
            if (isset($schema[$keyword]) && \is_array($schema[$keyword])) {
                return implode($glue, array_map(static fn (mixed $part): string => self::typeOf(self::map($part)), $schema[$keyword]));
            }
        }

        $types = \is_array($schema['type'] ?? null) ? self::strings($schema['type']) : self::strings([$schema['type'] ?? null]);
        if ([] === $types) {
            return isset($schema['enum']) ? 'enum' : 'mixed';
        }

        $compact = [];
        foreach ($types as $type) {
            $compact[] = match ($type) {
                'array' => self::typeOf(self::map($schema['items'] ?? [])).'[]',
                'object' => isset($schema['additionalProperties']) && \is_array($schema['additionalProperties'])
                    ? 'map<'.self::typeOf(self::map($schema['additionalProperties'])).'>'
                    : 'object',
                default => $type,
            };
        }

        return implode('|', $compact);
    }

    private static function refName(string $ref): string
    {
        return substr($ref, (int) strrpos($ref, '/') + 1);
    }

    /**
     * @return array<string, mixed>
     */
    private static function map(mixed $value): array
    {
        if (!\is_array($value)) {
            return [];
        }

        $map = [];
        foreach ($value as $key => $item) {
            $map[(string) $key] = $item;
        }

        return $map;
    }

    /**
     * @return list<string>
     */
    private static function strings(mixed $value): array
    {
        return \is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
    }
}
