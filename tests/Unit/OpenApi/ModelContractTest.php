<?php

namespace CleverCloud\Sdk\Tests\Unit\OpenApi;

use CleverCloud\Sdk\Tests\OpenApi\ModelSchemaMap;
use CleverCloud\Sdk\Tests\OpenApi\SpecExtract;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Each mapped model may only read payload keys its spec schema declares,
 * besides the drift already listed in {@see ModelSchemaMap}. Unmapped models
 * are not checked.
 */
#[CoversNothing]
final class ModelContractTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string, string, list<string>}>
     */
    public static function mappedModels(): iterable
    {
        foreach (ModelSchemaMap::entries() as $class => $entry) {
            yield substr($class, (int) strrpos($class, '\\') + 1) => [$class, $entry['schema'], $entry['knownDrift']];
        }
    }

    /**
     * @param class-string $class
     * @param list<string> $knownDrift
     */
    #[DataProvider('mappedModels')]
    public function testModelKeysExistInSchema(string $class, string $schema, array $knownDrift): void
    {
        $extract = SpecExtract::load();

        self::assertTrue(class_exists($class), \sprintf('Mapped model %s does not exist.', $class));
        self::assertArrayHasKey($schema, $extract->schemas, \sprintf('Schema %s is gone from the extract; update tests/OpenApi/ModelSchemaMap.php.', $schema));

        $unexpected = array_values(array_diff(ModelSchemaMap::drift($class, $extract), $knownDrift));

        self::assertSame([], $unexpected, \sprintf('%s reads keys schema %s does not declare.', $class, $schema));
    }
}
