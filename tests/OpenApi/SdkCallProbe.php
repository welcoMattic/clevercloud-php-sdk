<?php

namespace CleverCloud\Sdk\Tests\OpenApi;

use CleverCloud\Sdk\Resource\AbstractResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use DateTimeImmutable;
use DateTimeInterface;
use Generator;
use LogicException;

use const PHP_URL_HOST;
use const PHP_URL_PATH;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionEnum;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use SplFileInfo;
use stdClass;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Throwable;

/**
 * Discovers the HTTP endpoints the SDK calls by running it, not by parsing it.
 *
 * Every concrete resource under `src/Resource` is built against a recording
 * {@see MockHttpClient}, then each public method is invoked twice through
 * reflection: once with required arguments only, once with every argument
 * filled. That covers both owner variants (`/self` and `/organisations/{id}`)
 * of the many methods taking a nullable `$organisationId`.
 *
 * Synthesised string arguments are markers; any path segment containing one
 * is normalised to `{}`. Responses are a 200 JSON body whose `id` is also a
 * marker, so a method that chains calls (logs resolve the owner id through
 * `/v2/self` first) keeps going. Mapping errors are swallowed: the request
 * was recorded before the response was decoded.
 */
final class SdkCallProbe
{
    public const string DEFAULT_HOST = 'api.clever-cloud.com';

    private const string MARKER = 'probe_arg_';
    private const int INT_MARKER = 987654321;

    /** @var list<array{method: string, url: string}> */
    private array $recorded = [];

    private int $counter = 0;

    public function __construct(
        private readonly string $sourceDir,
    ) {
    }

    public static function forRepository(): self
    {
        return new self(\dirname(__DIR__, 2).'/src/Resource');
    }

    /**
     * @return list<SdkCall> sorted and deduplicated by (method, path, origin)
     */
    public function calls(): array
    {
        $calls = [];
        foreach ($this->resourceClasses() as $class) {
            foreach ($this->publicMethods($class) as $method) {
                $origin = $class->getShortName().'::'.$method->getName();
                $found = [];
                foreach ([false, true] as $fillOptional) {
                    foreach ($this->invoke($class, $method, $fillOptional) as $request) {
                        $found[] = new SdkCall($request['method'], self::normalise($request['url']), $origin);
                    }
                }
                if ([] === $found) {
                    throw new LogicException(\sprintf('%s issued no HTTP request under the probe; make it reachable with synthesised arguments or adapt %s.', $origin, self::class));
                }
                foreach ($found as $call) {
                    $calls[$call->key().' '.$origin] = $call;
                }
            }
        }
        ksort($calls);

        return array_values($calls);
    }

    /**
     * @return list<ReflectionClass<AbstractResource>>
     */
    private function resourceClasses(): array
    {
        $classes = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->sourceDir, RecursiveDirectoryIterator::SKIP_DOTS));
        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if ('php' !== $file->getExtension()) {
                continue;
            }
            $relative = substr($file->getPathname(), \strlen($this->sourceDir) + 1, -4);
            $name = 'CleverCloud\\Sdk\\Resource\\'.str_replace('/', '\\', $relative);
            if (!class_exists($name)) {
                continue;
            }
            $class = new ReflectionClass($name);
            if ($class->isAbstract() || !$class->isSubclassOf(AbstractResource::class)) {
                continue;
            }
            /** @var ReflectionClass<AbstractResource> $class */
            $classes[$class->getName()] = $class;
        }
        ksort($classes);

        return array_values($classes);
    }

    /**
     * @param ReflectionClass<AbstractResource> $class
     *
     * @return list<ReflectionMethod>
     */
    private function publicMethods(ReflectionClass $class): array
    {
        $methods = [];
        foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic() || $method->isConstructor() || str_starts_with($method->getName(), '__')) {
                continue;
            }
            $methods[] = $method;
        }

        return $methods;
    }

    /**
     * @param ReflectionClass<AbstractResource> $class
     *
     * @return list<array{method: string, url: string}>
     */
    private function invoke(ReflectionClass $class, ReflectionMethod $method, bool $fillOptional): array
    {
        $this->recorded = [];
        $mock = new MockHttpClient(function (string $httpMethod, string $url, array $options): MockResponse {
            $this->recorded[] = ['method' => $httpMethod, 'url' => $url];
            if (\in_array('Accept: text/event-stream', (array) ($options['headers'] ?? []), true)) {
                return new MockResponse('', ['response_headers' => ['content-type' => 'text/event-stream']]);
            }

            return ResourceFactory::jsonResponse(200, ['id' => self::MARKER.'response']);
        });
        $resource = $class->newInstance(ResourceFactory::http($mock), ResourceFactory::mapper());

        $arguments = [];
        foreach ($method->getParameters() as $parameter) {
            if (!$fillOptional && $parameter->isOptional()) {
                break;
            }
            $arguments[] = $this->argumentFor($parameter, $class->getShortName().'::'.$method->getName());
        }

        try {
            $result = $method->invokeArgs($resource, $arguments);
            if ($result instanceof Generator) {
                $result->current();
            }
            // Lazy responses settle on destruction; do it while errors are still caught.
            unset($result, $resource, $mock);
        } catch (Throwable) {
            // Expected: the synthesised response rarely maps onto the model.
        }

        return $this->recorded;
    }

    private function argumentFor(ReflectionParameter $parameter, string $origin): mixed
    {
        $type = $parameter->getType();
        $candidates = match (true) {
            $type instanceof ReflectionNamedType => [$type],
            $type instanceof ReflectionUnionType, $type instanceof ReflectionIntersectionType => $type->getTypes(),
            default => [],
        };
        if ([] === $candidates) {
            return $this->marker();
        }

        foreach ($candidates as $candidate) {
            if (!$candidate instanceof ReflectionNamedType) {
                continue;
            }
            $value = $this->valueFor($candidate->getName());
            if (null !== $value) {
                return $value;
            }
        }
        if (null !== $type && $type->allowsNull()) {
            return null;
        }

        throw new LogicException(\sprintf('Cannot synthesise an argument for %s($%s); teach %s::valueFor() about its type.', $origin, $parameter->getName(), self::class));
    }

    private function valueFor(string $type): mixed
    {
        if (enum_exists($type)) {
            $cases = new ReflectionEnum($type)->getCases();

            return [] === $cases ? null : $cases[0]->getValue();
        }

        return match ($type) {
            'string', 'mixed' => $this->marker(),
            'int' => self::INT_MARKER,
            'float' => 1.5,
            'bool', 'true' => true,
            'false' => false,
            'array', 'iterable' => [],
            DateTimeInterface::class, DateTimeImmutable::class => new DateTimeImmutable('@0'),
            stdClass::class, 'object' => new stdClass(),
            default => null,
        };
    }

    private function marker(): string
    {
        return self::MARKER.$this->counter++;
    }

    /**
     * Turns a recorded URL into the key the drift check compares: the path
     * (query dropped), every synthesised segment replaced with `{}`, and the
     * host prepended when it is not the main API host.
     */
    private static function normalise(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);
        $path = \is_string($path) ? rtrim($path, '/') : '';

        $segments = [];
        foreach (explode('/', ltrim($path, '/')) as $segment) {
            $isMarker = str_contains($segment, self::MARKER) || (string) self::INT_MARKER === $segment;
            $segments[] = $isMarker ? '{}' : $segment;
        }
        $path = '/'.implode('/', $segments);

        return \is_string($host) && self::DEFAULT_HOST !== $host ? $host.$path : $path;
    }
}
