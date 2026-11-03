<?php

/*
 * Maintenance entry point for the committed OpenAPI extract.
 *
 *   php tools/openapi.php check     fetch the live spec, exit 1 when its sha256 differs from the extract
 *   php tools/openapi.php refresh   fetch the live spec and regenerate tests/Fixtures/openapi/extract.json
 *   php tools/openapi.php drift     offline: SDK calls vs extract, coverage list, model contract
 *
 * `--from=<file>` reads a local copy of the spec instead of fetching it (check, refresh).
 */

use CleverCloud\Sdk\Tests\OpenApi\Allowlist;
use CleverCloud\Sdk\Tests\OpenApi\DriftCheck;
use CleverCloud\Sdk\Tests\OpenApi\ModelSchemaMap;
use CleverCloud\Sdk\Tests\OpenApi\SdkCallProbe;
use CleverCloud\Sdk\Tests\OpenApi\SpecExtract;
use Symfony\Component\HttpClient\HttpClient;

require \dirname(__DIR__).'/vendor/autoload.php';

$arguments = array_values(array_filter((array) ($_SERVER['argv'] ?? []), 'is_string'));
$command = $arguments[1] ?? '';
$from = null;
foreach (\array_slice($arguments, 2) as $option) {
    if (str_starts_with($option, '--from=')) {
        $from = substr($option, 7);
    }
}

/**
 * Returns the raw document plus its `x-build-ref` header, the commit the
 * document was built from (null when read from a local file).
 *
 * @return array{string, ?string}
 */
$fetch = static function () use ($from): array {
    if (null !== $from) {
        $raw = file_get_contents($from);
        if (false === $raw) {
            fwrite(\STDERR, "Cannot read {$from}.\n");
            exit(2);
        }

        return [$raw, null];
    }

    $response = HttpClient::create(['timeout' => 30])->request('GET', SpecExtract::SOURCE_URL);
    $headers = $response->getHeaders();

    return [$response->getContent(), $headers['x-build-ref'][0] ?? null];
};

switch ($command) {
    case 'check':
        $committed = SpecExtract::load();
        [$raw, $buildRef] = $fetch();
        $sha256 = hash('sha256', $raw);
        if ($sha256 === $committed->sha256) {
            echo "OpenAPI spec unchanged since {$committed->fetchedAt} (sha256 {$sha256}).\n";
            exit(0);
        }
        echo "OpenAPI spec changed.\n"
            ."  committed: {$committed->sha256} (fetched {$committed->fetchedAt}, build ".($committed->buildRef ?? 'unknown').")\n"
            ."  live:      {$sha256} (build ".($buildRef ?? 'unknown').")\n";
        echo "Run `composer openapi:refresh`, then `composer openapi:drift`, and commit the new extract.\n";
        exit(1);

    case 'refresh':
        $path = SpecExtract::defaultPath();
        $previous = is_file($path) ? SpecExtract::load($path) : null;
        [$raw, $buildRef] = $fetch();
        $sha256 = hash('sha256', $raw);
        // Keep the previous fetch date and build ref when the document did not move, so a no-op refresh leaves no diff.
        $unchanged = $sha256 === $previous?->sha256;
        $extract = SpecExtract::fromRawSpec(
            $raw,
            $unchanged ? $previous->fetchedAt : gmdate('Y-m-d'),
            buildRef: $unchanged ? ($previous->buildRef ?? $buildRef) : $buildRef,
        );
        file_put_contents($path, $extract->toJson());
        echo $sha256 === $previous?->sha256
            ? "sha256 unchanged ({$sha256}); extract regenerated.\n"
            : \sprintf("sha256 changed: %s -> %s; extract regenerated.\n", $previous->sha256 ?? 'none', $sha256);
        echo \sprintf("%d operations, %d schemas written to %s\n", \count($extract->operations), \count($extract->schemas), $path);
        exit(0);

    case 'drift':
        $extract = SpecExtract::load();
        $check = new DriftCheck($extract, SdkCallProbe::forRepository()->calls(), Allowlist::entries());
        echo "Extract: {$extract->source} sha256 {$extract->sha256} fetched {$extract->fetchedAt} build ".($extract->buildRef ?? 'unknown')."\n\n";
        echo $check->report(), "\n", ModelSchemaMap::report($extract);
        exit([] === $check->missing && [] === ModelSchemaMap::unexpectedDrift($extract) ? 0 : 1);

    default:
        fwrite(\STDERR, "Usage: php tools/openapi.php check|refresh|drift [--from=<file>]\n");
        exit(2);
}
