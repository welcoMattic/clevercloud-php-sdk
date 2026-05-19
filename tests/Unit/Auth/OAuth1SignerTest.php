<?php

namespace CleverCloud\Sdk\Tests\Unit\Auth;

use CleverCloud\Sdk\Auth\Credentials;
use CleverCloud\Sdk\Auth\NonceGenerator;
use CleverCloud\Sdk\Auth\OAuth1Signer;
use DateTimeImmutable;
use DateTimeZone;

use const JSON_THROW_ON_ERROR;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Psr\Http\Message\RequestInterface;

#[CoversClass(OAuth1Signer::class)]
final class OAuth1SignerTest extends TestCase
{
    private const string CONSUMER_KEY = 'consumer-key';
    private const string CONSUMER_SECRET = 'consumer-secret';
    private const string TOKEN = 'user-token';
    private const string TOKEN_SECRET = 'user-token-secret';
    private const int FIXED_TIMESTAMP = 1700000000;
    private const string FIXED_NONCE = 'abc123def456';

    private Psr17Factory $factory;
    private OAuth1Signer $signer;
    private Credentials $threeLegged;

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
        $this->signer = new OAuth1Signer(
            new FrozenClock(self::FIXED_TIMESTAMP),
            new StaticNonceGenerator(self::FIXED_NONCE),
        );
        $this->threeLegged = new Credentials(
            self::CONSUMER_KEY,
            self::CONSUMER_SECRET,
            self::TOKEN,
            self::TOKEN_SECRET,
        );
    }

    public function testSignsGetRequestWithExpectedHmacSha512Signature(): void
    {
        $request = $this->factory->createRequest('GET', 'https://api.clever-cloud.com/v2/self');

        $signed = $this->signer->sign($request, $this->threeLegged);

        $expectedSignature = $this->computeExpectedSignature(
            'GET',
            'https://api.clever-cloud.com/v2/self',
            [
                'oauth_consumer_key' => self::CONSUMER_KEY,
                'oauth_nonce' => self::FIXED_NONCE,
                'oauth_signature_method' => 'HMAC-SHA512',
                'oauth_timestamp' => (string) self::FIXED_TIMESTAMP,
                'oauth_token' => self::TOKEN,
                'oauth_version' => '1.0',
            ],
            self::CONSUMER_SECRET,
            self::TOKEN_SECRET,
        );

        self::assertOAuthHeaderContains($signed, [
            'oauth_consumer_key' => self::CONSUMER_KEY,
            'oauth_nonce' => self::FIXED_NONCE,
            'oauth_signature_method' => 'HMAC-SHA512',
            'oauth_timestamp' => (string) self::FIXED_TIMESTAMP,
            'oauth_token' => self::TOKEN,
            'oauth_version' => '1.0',
            'oauth_signature' => $expectedSignature,
        ]);
    }

    public function testIncludesQueryParametersInBaseString(): void
    {
        $request = $this->factory->createRequest('GET', 'https://api.clever-cloud.com/v2/organisations?fields=id&fields=name&limit=10');

        $signed = $this->signer->sign($request, $this->threeLegged);

        $expected = $this->computeExpectedSignature(
            'GET',
            'https://api.clever-cloud.com/v2/organisations',
            [
                'oauth_consumer_key' => self::CONSUMER_KEY,
                'oauth_nonce' => self::FIXED_NONCE,
                'oauth_signature_method' => 'HMAC-SHA512',
                'oauth_timestamp' => (string) self::FIXED_TIMESTAMP,
                'oauth_token' => self::TOKEN,
                'oauth_version' => '1.0',
                'fields' => ['id', 'name'],
                'limit' => '10',
            ],
            self::CONSUMER_SECRET,
            self::TOKEN_SECRET,
        );

        self::assertSame($expected, $this->extractParam($signed, 'oauth_signature'));
    }

    public function testIncludesFormEncodedBodyInBaseString(): void
    {
        $body = $this->factory->createStream('name=app&zone=par');
        $request = $this->factory
            ->createRequest('POST', 'https://api.clever-cloud.com/v2/self/applications')
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody($body);

        $signed = $this->signer->sign($request, $this->threeLegged);

        $expected = $this->computeExpectedSignature(
            'POST',
            'https://api.clever-cloud.com/v2/self/applications',
            [
                'oauth_consumer_key' => self::CONSUMER_KEY,
                'oauth_nonce' => self::FIXED_NONCE,
                'oauth_signature_method' => 'HMAC-SHA512',
                'oauth_timestamp' => (string) self::FIXED_TIMESTAMP,
                'oauth_token' => self::TOKEN,
                'oauth_version' => '1.0',
                'name' => 'app',
                'zone' => 'par',
            ],
            self::CONSUMER_SECRET,
            self::TOKEN_SECRET,
        );

        self::assertSame($expected, $this->extractParam($signed, 'oauth_signature'));
    }

    public function testJsonBodyIsExcludedFromBaseString(): void
    {
        $body = $this->factory->createStream(json_encode(['name' => 'app', 'zone' => 'par'], JSON_THROW_ON_ERROR));
        $request = $this->factory
            ->createRequest('POST', 'https://api.clever-cloud.com/v2/self/applications')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($body);

        $signed = $this->signer->sign($request, $this->threeLegged);

        $expected = $this->computeExpectedSignature(
            'POST',
            'https://api.clever-cloud.com/v2/self/applications',
            [
                'oauth_consumer_key' => self::CONSUMER_KEY,
                'oauth_nonce' => self::FIXED_NONCE,
                'oauth_signature_method' => 'HMAC-SHA512',
                'oauth_timestamp' => (string) self::FIXED_TIMESTAMP,
                'oauth_token' => self::TOKEN,
                'oauth_version' => '1.0',
            ],
            self::CONSUMER_SECRET,
            self::TOKEN_SECRET,
        );

        self::assertSame($expected, $this->extractParam($signed, 'oauth_signature'));
    }

    public function testFormEncodedBodyDetectionStripsCharsetSuffix(): void
    {
        $body = $this->factory->createStream('foo=bar');
        $request = $this->factory
            ->createRequest('POST', 'https://api.clever-cloud.com/v2/self')
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded; charset=utf-8')
            ->withBody($body);

        $signed = $this->signer->sign($request, $this->threeLegged);

        $expected = $this->computeExpectedSignature(
            'POST',
            'https://api.clever-cloud.com/v2/self',
            [
                'oauth_consumer_key' => self::CONSUMER_KEY,
                'oauth_nonce' => self::FIXED_NONCE,
                'oauth_signature_method' => 'HMAC-SHA512',
                'oauth_timestamp' => (string) self::FIXED_TIMESTAMP,
                'oauth_token' => self::TOKEN,
                'oauth_version' => '1.0',
                'foo' => 'bar',
            ],
            self::CONSUMER_SECRET,
            self::TOKEN_SECRET,
        );

        self::assertSame($expected, $this->extractParam($signed, 'oauth_signature'));
    }

    public function testRewindsSeekableBodyAfterReading(): void
    {
        $body = $this->factory->createStream('a=1');
        $request = $this->factory
            ->createRequest('POST', 'https://api.clever-cloud.com/v2/self')
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody($body);

        $signed = $this->signer->sign($request, $this->threeLegged);

        self::assertSame('a=1', (string) $signed->getBody());
    }

    public function testOmitsOAuthTokenWhenAbsent(): void
    {
        $twoLegged = new Credentials(self::CONSUMER_KEY, self::CONSUMER_SECRET);
        $request = $this->factory->createRequest('POST', 'https://api.clever-cloud.com/v2/oauth/request_token');

        $signed = $this->signer->sign($request, $twoLegged, ['oauth_callback' => 'oob']);

        $header = $signed->getHeaderLine('Authorization');
        self::assertStringNotContainsString('oauth_token=', $header);
        self::assertStringContainsString('oauth_callback="oob"', $header);

        $expected = $this->computeExpectedSignature(
            'POST',
            'https://api.clever-cloud.com/v2/oauth/request_token',
            [
                'oauth_callback' => 'oob',
                'oauth_consumer_key' => self::CONSUMER_KEY,
                'oauth_nonce' => self::FIXED_NONCE,
                'oauth_signature_method' => 'HMAC-SHA512',
                'oauth_timestamp' => (string) self::FIXED_TIMESTAMP,
                'oauth_version' => '1.0',
            ],
            self::CONSUMER_SECRET,
            '',
        );

        self::assertSame($expected, $this->extractParam($signed, 'oauth_signature'));
    }

    public function testPercentEncodesUnicodeValuesPerRfc3986(): void
    {
        $request = $this->factory->createRequest('GET', 'https://api.clever-cloud.com/v2/search?q=caf%C3%A9');

        $signed = $this->signer->sign($request, $this->threeLegged);

        $expected = $this->computeExpectedSignature(
            'GET',
            'https://api.clever-cloud.com/v2/search',
            [
                'oauth_consumer_key' => self::CONSUMER_KEY,
                'oauth_nonce' => self::FIXED_NONCE,
                'oauth_signature_method' => 'HMAC-SHA512',
                'oauth_timestamp' => (string) self::FIXED_TIMESTAMP,
                'oauth_token' => self::TOKEN,
                'oauth_version' => '1.0',
                'q' => 'café',
            ],
            self::CONSUMER_SECRET,
            self::TOKEN_SECRET,
        );

        self::assertSame($expected, $this->extractParam($signed, 'oauth_signature'));
    }

    public function testStripsDefaultHttpsPortFromBaseUri(): void
    {
        $request = $this->factory->createRequest('GET', 'https://api.clever-cloud.com:443/v2/self');

        $signed = $this->signer->sign($request, $this->threeLegged);

        $expected = $this->computeExpectedSignature(
            'GET',
            'https://api.clever-cloud.com/v2/self',
            [
                'oauth_consumer_key' => self::CONSUMER_KEY,
                'oauth_nonce' => self::FIXED_NONCE,
                'oauth_signature_method' => 'HMAC-SHA512',
                'oauth_timestamp' => (string) self::FIXED_TIMESTAMP,
                'oauth_token' => self::TOKEN,
                'oauth_version' => '1.0',
            ],
            self::CONSUMER_SECRET,
            self::TOKEN_SECRET,
        );

        self::assertSame($expected, $this->extractParam($signed, 'oauth_signature'));
    }

    public function testProducesStableSignatureAcrossInvocations(): void
    {
        $request = $this->factory->createRequest('GET', 'https://api.clever-cloud.com/v2/self');

        $first = $this->signer->sign($request, $this->threeLegged)->getHeaderLine('Authorization');
        $second = $this->signer->sign($request, $this->threeLegged)->getHeaderLine('Authorization');

        self::assertSame($first, $second);
    }

    /**
     * @param array<string, string|list<string>> $expectations
     */
    private function assertOAuthHeaderContains(RequestInterface $signed, array $expectations): void
    {
        $header = $signed->getHeaderLine('Authorization');
        self::assertStringStartsWith('OAuth ', $header);

        foreach ($expectations as $key => $value) {
            if (\is_array($value)) {
                continue;
            }
            $expected = \sprintf('%s="%s"', rawurlencode($key), rawurlencode($value));
            self::assertStringContainsString($expected, $header, \sprintf('Missing %s in header: %s', $key, $header));
        }
    }

    private function extractParam(RequestInterface $signed, string $name): string
    {
        $header = $signed->getHeaderLine('Authorization');
        $needle = rawurlencode($name).'="';
        $start = strpos($header, $needle);
        if (false === $start) {
            self::fail("Header missing param {$name}: {$header}");
        }
        $valueStart = $start + \strlen($needle);
        $end = strpos($header, '"', $valueStart);
        if (false === $end) {
            self::fail("Header param {$name} not terminated: {$header}");
        }

        return rawurldecode(substr($header, $valueStart, $end - $valueStart));
    }

    /**
     * Recomputes the expected HMAC-SHA512 signature for a known request shape,
     * giving the test an independent verification of {@see OAuth1Signer::computeSignature}.
     *
     * @param array<string, string|list<string>> $allParams oauth_* + query + form-body params
     */
    private function computeExpectedSignature(
        string $method,
        string $baseUri,
        array $allParams,
        string $consumerSecret,
        string $tokenSecret,
    ): string {
        $pairs = [];
        foreach ($allParams as $key => $value) {
            if (\is_array($value)) {
                foreach ($value as $entry) {
                    $pairs[] = [rawurlencode($key), rawurlencode($entry)];
                }
                continue;
            }
            $pairs[] = [rawurlencode($key), rawurlencode($value)];
        }
        usort($pairs, static fn (array $a, array $b): int => [$a[0], $a[1]] <=> [$b[0], $b[1]]);

        $normalised = implode('&', array_map(static fn (array $p): string => $p[0].'='.$p[1], $pairs));

        $baseString = strtoupper($method)
            .'&'.rawurlencode($baseUri)
            .'&'.rawurlencode($normalised);

        $signingKey = rawurlencode($consumerSecret).'&'.rawurlencode($tokenSecret);

        return base64_encode(hash_hmac('sha512', $baseString, $signingKey, true));
    }
}

final class FrozenClock implements ClockInterface
{
    public function __construct(private readonly int $timestamp)
    {
    }

    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('@'.$this->timestamp)->setTimezone(new DateTimeZone('UTC'));
    }
}

final class StaticNonceGenerator implements NonceGenerator
{
    public function __construct(private readonly string $value)
    {
    }

    public function generate(): string
    {
        return $this->value;
    }
}
