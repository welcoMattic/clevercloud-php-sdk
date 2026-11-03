<?php

namespace CleverCloud\Sdk\Tests\OpenApi;

use AutoMapper\Attribute\MapFrom;
use CleverCloud\Sdk\Model;
use ReflectionClass;
use ReflectionProperty;

/**
 * Hand-maintained map from SDK models to the spec schema describing the same
 * payload. Only clear matches are listed; a model without an entry is not
 * checked (its family is absent from the spec, or no schema fits).
 *
 * `knownDrift` lists payload keys the model reads but the schema lacks. The
 * contract test fails only on drift not listed here, so a fix never breaks
 * it; `composer openapi:drift` reports entries that no longer drift.
 */
final class ModelSchemaMap
{
    /**
     * @return array<class-string, array{schema: string, knownDrift: list<string>}>
     */
    public static function entries(): array
    {
        return [
            Model\Addon::class => ['schema' => 'AddonView', 'knownDrift' => []],
            Model\AddonPlan::class => ['schema' => 'AddonPlanView', 'knownDrift' => []],
            Model\AddonProvider::class => ['schema' => 'AddonProviderInfoFullView', 'knownDrift' => []],
            // Spec field is `last_deploy`: the camelCase property never gets populated.
            Model\Application::class => ['schema' => 'ApplicationView', 'knownDrift' => ['lastDeploy']],
            // `instanceId` is not part of the documented deployment payload.
            Model\Deployment::class => ['schema' => 'DeploymentView', 'knownDrift' => ['instanceId']],
            Model\DeploymentAuthor::class => ['schema' => 'Author', 'knownDrift' => []],
            // Spec field is `machine_learning`.
            Model\Flavor::class => ['schema' => 'Flavor', 'knownDrift' => ['machineLearning']],
            Model\Member::class => ['schema' => 'MemberView', 'knownDrift' => []],
            // Spec field is `namespace`.
            Model\Namespace_::class => ['schema' => 'NamespaceView', 'knownDrift' => ['name']],
            // The spec has `label` and `networkIp`, no `name` nor `cidr`.
            Model\NetworkGroup::class => ['schema' => 'NetworkGroup', 'knownDrift' => ['name', 'cidr']],
            Model\NetworkGroupMember::class => ['schema' => 'NetworkGroupComponent', 'knownDrift' => []],
            // The secret comes from the separate `/consumers/{key}/secret` route.
            Model\OAuthConsumer::class => ['schema' => 'OAuth1ConsumerView', 'knownDrift' => ['secret']],
            // Spec fields are `VAT` (upper case) and no creation date.
            Model\Organisation::class => ['schema' => 'OrganisationView', 'knownDrift' => ['vat', 'creation_date']],
            // The documented body is `{offloadPolicies, retentionPolicies}`: the model matches none of it.
            Model\PulsarPolicy::class => ['schema' => 'StoragePolicies', 'knownDrift' => ['addonId', 'namespace', 'retentionTimeInMinutes', 'retentionSizeInMB', 'messageTTLInSeconds', 'rawPolicy']],
            Model\SshKey::class => ['schema' => 'SshKeyView', 'knownDrift' => []],
            Model\TcpRedirection::class => ['schema' => 'TcpRedirView', 'knownDrift' => []],
            Model\User::class => ['schema' => 'UserView', 'knownDrift' => []],
            Model\Vhost::class => ['schema' => 'VhostView', 'knownDrift' => []],
            Model\Zone::class => ['schema' => 'Region', 'knownDrift' => []],
        ];
    }

    /**
     * Payload keys a model reads: the `#[MapFrom(property: ...)]` source when
     * present, the property name otherwise; ignored properties are skipped.
     *
     * @param class-string $class
     *
     * @return list<string>
     */
    public static function payloadKeys(string $class): array
    {
        $keys = [];
        foreach (new ReflectionClass($class)->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $key = $property->getName();
            foreach ($property->getAttributes(MapFrom::class) as $attribute) {
                $mapFrom = $attribute->newInstance();
                if (true === $mapFrom->ignore) {
                    continue 2;
                }
                $key = $mapFrom->property ?? $key;
            }
            $keys[] = $key;
        }

        return $keys;
    }

    /**
     * @param class-string $class
     *
     * @return list<string> payload keys of the model absent from the schema
     */
    public static function drift(string $class, SpecExtract $extract): array
    {
        $schema = self::entries()[$class]['schema'] ?? '';
        $properties = $extract->schemas[$schema]['properties'] ?? [];

        return array_values(array_diff(self::payloadKeys($class), array_keys($properties)));
    }

    /**
     * @return array<class-string, list<string>> drift not listed in `knownDrift`, per model
     */
    public static function unexpectedDrift(SpecExtract $extract): array
    {
        $unexpected = [];
        foreach (self::entries() as $class => $entry) {
            $keys = array_values(array_diff(self::drift($class, $extract), $entry['knownDrift']));
            if ([] !== $keys) {
                $unexpected[$class] = $keys;
            }
        }

        return $unexpected;
    }

    public static function report(SpecExtract $extract): string
    {
        $lines = ['== Model contract (model payload keys absent from the mapped schema) =='];
        foreach (self::entries() as $class => $entry) {
            $drift = self::drift($class, $extract);
            $unexpected = array_diff($drift, $entry['knownDrift']);
            $fixed = array_diff($entry['knownDrift'], $drift);
            $status = [] === $unexpected ? 'ok' : 'FAIL unexpected: '.implode(', ', $unexpected);
            if ([] !== $entry['knownDrift'] && [] === $fixed) {
                $status .= ' (known drift: '.implode(', ', $entry['knownDrift']).')';
            }
            if ([] !== $fixed) {
                $status .= ' (no longer drifting, prune knownDrift: '.implode(', ', $fixed).')';
            }
            $lines[] = \sprintf('  %s -> %s: %s', substr($class, (int) strrpos($class, '\\') + 1), $entry['schema'], $status);
        }

        $mapped = array_column(self::entries(), 'schema');
        $unmapped = array_values(array_diff(array_keys($extract->schemas), $mapped));
        $lines[] = '';
        $lines[] = \sprintf('== Spec schemas without a mapped model (%d) ==', \count($unmapped));
        $lines[] = '  '.implode(' ', $unmapped);

        return implode("\n", $lines)."\n";
    }
}
