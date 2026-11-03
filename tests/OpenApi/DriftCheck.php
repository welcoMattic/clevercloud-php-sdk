<?php

namespace CleverCloud\Sdk\Tests\OpenApi;

/**
 * Compares the endpoints the SDK calls with the operations of the extract.
 *
 * Matching is per segment and per method: a spec `{param}` matches any SDK
 * segment, an SDK parameter (`{}`) only matches a spec `{param}`, literals
 * must be equal. When several operations match, the most literal one wins
 * (`/vhosts/favourite` beats `/vhosts/{fqdn}`).
 *
 * A call to `/vN/x` that only matches a spec path `/x` is a soft match: the
 * spec documents a few routes without their version prefix.
 *
 * @phpstan-import-type Operation from SpecExtract
 */
final class DriftCheck
{
    /** @var array<string, string> SDK key => matched spec "METHOD path" */
    public private(set) array $matched = [];

    /** @var array<string, string> SDK key => matched unprefixed spec "METHOD path" */
    public private(set) array $softMatched = [];

    /** @var array<string, string> SDK key => matched spec "METHOD path" where an SDK literal fills a spec parameter */
    public private(set) array $literalBindings = [];

    /** @var array<string, string> SDK key => allowlist reason */
    public private(set) array $allowlisted = [];

    /** @var array<string, list<string>> SDK key => origins, absent from the spec and not allowlisted */
    public private(set) array $missing = [];

    /** @var list<Operation> spec operations no SDK call reaches */
    public private(set) array $uncovered = [];

    /** @var list<string> allowlist entries that matched nothing, or only calls the spec now documents */
    public private(set) array $staleAllowlist = [];

    /**
     * @param list<SdkCall>                                                $calls
     * @param list<array{method: string, pattern: string, reason: string}> $allowlist
     */
    public function __construct(SpecExtract $extract, array $calls, array $allowlist)
    {
        $origins = [];
        foreach ($calls as $call) {
            $origins[$call->key()][] = $call->origin;
        }
        ksort($origins);

        $covered = [];
        $allowlistUse = array_fill(0, \count($allowlist), false);
        foreach ($origins as $key => $from) {
            [$method, $path] = explode(' ', $key, 2);
            $operation = self::bestMatch($method, $path, $extract->operations);
            $soft = false;
            if (null === $operation && 1 === preg_match('#^/v\d+(/.*)$#', $path, $m)) {
                $operation = self::bestMatch($method, $m[1], $extract->operations);
                $soft = null !== $operation;
            }

            $entry = self::allowlistEntry($method, $path, $allowlist);
            if (null !== $entry && null === $operation) {
                $allowlistUse[$entry] = true;
                $this->allowlisted[$key] = $allowlist[$entry]['reason'];
                continue;
            }
            if (null === $operation) {
                $this->missing[$key] = array_values(array_unique($from));
                continue;
            }

            $id = $operation['method'].' '.$operation['path'];
            $covered[$id] = true;
            if (self::bindsLiteral($soft ? (string) preg_replace('#^/v\d+#', '', $path) : $path, $operation['path'])) {
                $this->literalBindings[$key] = $id;
            }
            if ($soft) {
                $this->softMatched[$key] = $id;
            } else {
                $this->matched[$key] = $id;
            }
        }

        foreach ($extract->operations as $operation) {
            if (!isset($covered[$operation['method'].' '.$operation['path']])) {
                $this->uncovered[] = $operation;
            }
        }
        foreach ($allowlist as $index => $entry) {
            if (!$allowlistUse[$index]) {
                $this->staleAllowlist[] = $entry['method'].' '.$entry['pattern'];
            }
        }
    }

    public function report(): string
    {
        $lines = [];
        $lines[] = \sprintf(
            'SDK endpoints: %d in spec, %d soft-matched, %d allowlisted, %d MISSING. Spec operations not covered: %d.',
            \count($this->matched),
            \count($this->softMatched),
            \count($this->allowlisted),
            \count($this->missing),
            \count($this->uncovered),
        );

        $lines[] = '';
        $lines[] = '== SDK calls absent from the spec and not allowlisted (failure) ==';
        foreach ($this->missing as $key => $from) {
            $lines[] = \sprintf('  %s  (%s)', $key, implode(', ', $from));
        }
        if ([] === $this->missing) {
            $lines[] = '  none';
        }

        $lines[] = '';
        $lines[] = '== SDK calls matched only without the version prefix (check the base URL) ==';
        foreach ($this->softMatched as $key => $operation) {
            $lines[] = \sprintf('  %s  ->  %s', $key, $operation);
        }
        if ([] === $this->softMatched) {
            $lines[] = '  none';
        }

        $lines[] = '';
        $lines[] = '== SDK literals filling a spec parameter (review: legit value or wrong route?) ==';
        foreach ($this->literalBindings as $key => $operation) {
            $lines[] = \sprintf('  %s  ->  %s', $key, $operation);
        }
        if ([] === $this->literalBindings) {
            $lines[] = '  none';
        }

        $lines[] = '';
        $lines[] = '== Stale allowlist entries (prune them) ==';
        foreach ($this->staleAllowlist as $entry) {
            $lines[] = '  '.$entry;
        }
        if ([] === $this->staleAllowlist) {
            $lines[] = '  none';
        }

        $lines[] = '';
        $lines[] = '== SDK calls allowlisted (known absent from the spec) ==';
        foreach ($this->allowlisted as $key => $reason) {
            $lines[] = \sprintf('  %s  [%s]', $key, $reason);
        }

        $lines[] = '';
        $lines[] = '== Spec operations not covered by the SDK (informational) ==';
        $byTag = [];
        foreach ($this->uncovered as $operation) {
            $byTag[$operation['tags'][0] ?? 'untagged'][] = $operation;
        }
        ksort($byTag);
        foreach ($byTag as $tag => $operations) {
            $lines[] = \sprintf('  [%s] %d', $tag, \count($operations));
            foreach ($operations as $operation) {
                $lines[] = \sprintf('    %s %s  %s', $operation['method'], $operation['path'], $operation['operationId']);
            }
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * @param list<Operation> $operations
     *
     * @return Operation|null
     */
    private static function bestMatch(string $method, string $path, array $operations): ?array
    {
        $segments = explode('/', trim($path, '/'));
        $best = null;
        $bestScore = -1;
        foreach ($operations as $operation) {
            if ($operation['method'] !== $method) {
                continue;
            }
            $score = self::score($segments, explode('/', trim($operation['path'], '/')));
            if ($score > $bestScore) {
                $best = $operation;
                $bestScore = $score;
            }
        }

        return $best;
    }

    /**
     * @param list<string> $sdk
     * @param list<string> $spec
     *
     * @return int number of literal segments matched, -1 when the paths differ
     */
    private static function score(array $sdk, array $spec): int
    {
        if (\count($sdk) !== \count($spec)) {
            return -1;
        }

        $literals = 0;
        foreach ($spec as $index => $segment) {
            if (str_starts_with($segment, '{') && str_ends_with($segment, '}')) {
                continue;
            }
            if ($segment !== $sdk[$index]) {
                return -1;
            }
            ++$literals;
        }

        return $literals;
    }

    private static function bindsLiteral(string $sdkPath, string $specPath): bool
    {
        $sdk = explode('/', trim($sdkPath, '/'));
        foreach (explode('/', trim($specPath, '/')) as $index => $segment) {
            if (str_starts_with($segment, '{') && '{}' !== ($sdk[$index] ?? '{}')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<array{method: string, pattern: string, reason: string}> $allowlist
     */
    private static function allowlistEntry(string $method, string $path, array $allowlist): ?int
    {
        foreach ($allowlist as $index => $entry) {
            if (('*' === $entry['method'] || $entry['method'] === $method) && fnmatch($entry['pattern'], $path)) {
                return $index;
            }
        }

        return null;
    }
}
