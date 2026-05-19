<?php

namespace CleverCloud\Sdk\Auth;

use Psr\Clock\ClockInterface;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\Clock\NativeClock;

/**
 * Signs a PSR-7 request with OAuth 1.0a using HMAC-SHA512 per RFC 5849.
 *
 * The signer is stateless aside from its injected `ClockInterface` and
 * `NonceGenerator`, which lets tests pin both for reproducible signatures.
 */
final readonly class OAuth1Signer
{
    public const string SIGNATURE_METHOD = 'HMAC-SHA512';

    public function __construct(
        private ClockInterface $clock = new NativeClock(),
        private NonceGenerator $nonceGenerator = new RandomNonceGenerator(),
    ) {
    }

    /**
     * @param array<string, scalar> $extraOAuthParams optional protocol params (oauth_callback, oauth_verifier, …)
     */
    public function sign(RequestInterface $request, Credentials $credentials, array $extraOAuthParams = []): RequestInterface
    {
        $oauthParams = [
            'oauth_consumer_key' => $credentials->consumerKey,
            'oauth_signature_method' => self::SIGNATURE_METHOD,
            'oauth_timestamp' => (string) $this->clock->now()->getTimestamp(),
            'oauth_nonce' => $this->nonceGenerator->generate(),
            'oauth_version' => '1.0',
        ];

        if (null !== $credentials->token) {
            $oauthParams['oauth_token'] = $credentials->token;
        }

        foreach ($extraOAuthParams as $key => $value) {
            $oauthParams[$key] = (string) $value;
        }

        $oauthParams['oauth_signature'] = $this->computeSignature($request, $credentials, $oauthParams);

        return $request->withHeader('Authorization', self::buildAuthorizationHeader($oauthParams));
    }

    /**
     * @param array<string, string> $oauthParams without `oauth_signature`
     */
    private function computeSignature(RequestInterface $request, Credentials $credentials, array $oauthParams): string
    {
        $pairs = [];
        foreach ($oauthParams as $key => $value) {
            $pairs[] = [$key, $value];
        }
        foreach (self::parseFormEncoded($request->getUri()->getQuery()) as $pair) {
            $pairs[] = $pair;
        }
        if (self::hasFormEncodedBody($request)) {
            $body = $request->getBody();
            $content = (string) $body;
            if ($body->isSeekable()) {
                $body->rewind();
            }
            foreach (self::parseFormEncoded($content) as $pair) {
                $pairs[] = $pair;
            }
        }

        $encoded = array_map(
            static fn (array $pair): array => [rawurlencode($pair[0]), rawurlencode($pair[1])],
            $pairs,
        );
        usort($encoded, static function (array $a, array $b): int {
            return [$a[0], $a[1]] <=> [$b[0], $b[1]];
        });
        $normalised = implode('&', array_map(
            static fn (array $pair): string => $pair[0].'='.$pair[1],
            $encoded,
        ));

        $baseString = strtoupper($request->getMethod())
            .'&'.rawurlencode(self::baseUri($request))
            .'&'.rawurlencode($normalised);

        $signingKey = rawurlencode($credentials->consumerSecret)
            .'&'.rawurlencode($credentials->tokenSecret ?? '');

        return base64_encode(hash_hmac('sha512', $baseString, $signingKey, true));
    }

    private static function baseUri(RequestInterface $request): string
    {
        $uri = $request->getUri();
        $scheme = strtolower($uri->getScheme());
        $host = strtolower($uri->getHost());
        $port = $uri->getPort();

        $isDefaultPort = (
            ('https' === $scheme && 443 === $port)
            || ('http' === $scheme && 80 === $port)
        );

        $authority = $host;
        if (null !== $port && !$isDefaultPort) {
            $authority .= ':'.$port;
        }

        $path = $uri->getPath();
        if ('' === $path) {
            $path = '/';
        }

        return $scheme.'://'.$authority.$path;
    }

    private static function hasFormEncodedBody(RequestInterface $request): bool
    {
        $contentType = $request->getHeaderLine('Content-Type');
        if ('' === $contentType) {
            return false;
        }

        $semi = strpos($contentType, ';');
        $type = trim(false === $semi ? $contentType : substr($contentType, 0, $semi));

        return 'application/x-www-form-urlencoded' === strtolower($type);
    }

    /**
     * Parses an `application/x-www-form-urlencoded` string while preserving
     * repeated keys (which `parse_str` would collapse into arrays).
     *
     * @return list<array{string, string}>
     */
    private static function parseFormEncoded(string $body): array
    {
        if ('' === $body) {
            return [];
        }

        $pairs = [];
        foreach (explode('&', $body) as $segment) {
            if ('' === $segment) {
                continue;
            }
            $eq = strpos($segment, '=');
            if (false === $eq) {
                $pairs[] = [urldecode($segment), ''];
                continue;
            }
            $pairs[] = [
                urldecode(substr($segment, 0, $eq)),
                urldecode(substr($segment, $eq + 1)),
            ];
        }

        return $pairs;
    }

    /**
     * @param array<string, string> $params
     */
    private static function buildAuthorizationHeader(array $params): string
    {
        ksort($params);

        $parts = [];
        foreach ($params as $key => $value) {
            $parts[] = rawurlencode($key).'="'.rawurlencode($value).'"';
        }

        return 'OAuth '.implode(', ', $parts);
    }
}
