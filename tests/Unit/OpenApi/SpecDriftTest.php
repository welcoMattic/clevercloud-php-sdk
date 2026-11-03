<?php

namespace CleverCloud\Sdk\Tests\Unit\OpenApi;

use CleverCloud\Sdk\Tests\OpenApi\Allowlist;
use CleverCloud\Sdk\Tests\OpenApi\DriftCheck;
use CleverCloud\Sdk\Tests\OpenApi\SdkCallProbe;
use CleverCloud\Sdk\Tests\OpenApi\SpecExtract;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Every endpoint the SDK calls must be documented in the committed spec
 * extract or allowlisted. Run `composer openapi:drift` for the full report.
 */
#[CoversNothing]
final class SpecDriftTest extends TestCase
{
    public function testEverySdkCallIsDocumentedOrAllowlisted(): void
    {
        $check = new DriftCheck(SpecExtract::load(), SdkCallProbe::forRepository()->calls(), Allowlist::entries());

        $missing = [];
        foreach ($check->missing as $key => $origins) {
            $missing[] = $key.' ('.implode(', ', $origins).')';
        }

        self::assertSame([], $missing, "SDK calls absent from the OpenAPI extract and not allowlisted.\nFix the route, or add an entry to tests/OpenApi/Allowlist.php with the reason. Details: composer openapi:drift");
    }

    public function testCommittedExtractIsPinnedAndClean(): void
    {
        $path = SpecExtract::defaultPath();
        $extract = SpecExtract::load($path);
        $raw = (string) file_get_contents($path);

        self::assertSame(SpecExtract::SOURCE_URL, $extract->source);
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $extract->sha256);
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $extract->fetchedAt);
        self::assertNotEmpty($extract->operations);
        self::assertNotEmpty($extract->schemas);
        self::assertStringNotContainsString("\u{2014}", $raw);
        self::assertStringNotContainsString("\u{2013}", $raw);
    }
}
