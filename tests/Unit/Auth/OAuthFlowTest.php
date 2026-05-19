<?php

namespace CleverCloud\Sdk\Tests\Unit\Auth;

use CleverCloud\Sdk\Auth\OAuth1Signer;
use CleverCloud\Sdk\Auth\OAuthFlow;
use CleverCloud\Sdk\Configuration;
use CleverCloud\Sdk\Exception\ApiException;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Clock\MockClock;

#[CoversClass(OAuthFlow::class)]
final class OAuthFlowTest extends TestCase
{
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
    }

    public function testRequestTokenParsesFormEncodedResponse(): void
    {
        $psr18 = new FlowRecorder($this->formResponse(200, 'oauth_token=req-token&oauth_token_secret=req-secret&oauth_callback_confirmed=true'));

        $token = $this->flow($psr18)->requestToken('ck', 'cs', 'https://app.example/callback');

        self::assertSame(['token' => 'req-token', 'tokenSecret' => 'req-secret'], $token);

        $sent = $psr18->lastRequest;
        self::assertNotNull($sent);
        self::assertSame('POST', $sent->getMethod());
        self::assertSame('https://api.clever-cloud.com/v2/oauth/request_token', (string) $sent->getUri());

        $authHeader = $sent->getHeaderLine('Authorization');
        self::assertStringStartsWith('OAuth ', $authHeader);
        self::assertStringContainsString('oauth_callback="https%3A%2F%2Fapp.example%2Fcallback"', $authHeader);
        self::assertStringNotContainsString('oauth_token=', $authHeader);
    }

    public function testAuthorizationUrlIncludesEncodedToken(): void
    {
        $url = $this->flow(new FlowRecorder($this->formResponse(200, '')))
            ->authorizationUrl('req-token/with special');

        self::assertSame(
            'https://api.clever-cloud.com/v2/oauth/authorize?oauth_token=req-token%2Fwith%20special',
            $url,
        );
    }

    public function testAccessTokenSendsVerifierInAuthHeader(): void
    {
        $psr18 = new FlowRecorder($this->formResponse(200, 'oauth_token=user-token&oauth_token_secret=user-secret'));

        $token = $this->flow($psr18)->accessToken('ck', 'cs', 'req-token', 'req-secret', 'verifier-xyz');

        self::assertSame(['token' => 'user-token', 'tokenSecret' => 'user-secret'], $token);

        $sent = $psr18->lastRequest;
        self::assertNotNull($sent);
        self::assertSame('https://api.clever-cloud.com/v2/oauth/access_token', (string) $sent->getUri());

        $authHeader = $sent->getHeaderLine('Authorization');
        self::assertStringContainsString('oauth_verifier="verifier-xyz"', $authHeader);
        self::assertStringContainsString('oauth_token="req-token"', $authHeader);
    }

    public function testThrowsApiExceptionOnNon2xxResponse(): void
    {
        $psr18 = new FlowRecorder($this->formResponse(403, 'invalid signature'));

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(403);
        $this->flow($psr18)->requestToken('ck', 'cs', 'https://app.example/cb');
    }

    public function testThrowsWhenResponseMissingExpectedFields(): void
    {
        $psr18 = new FlowRecorder($this->formResponse(200, 'something_else=true'));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('oauth_token');
        $this->flow($psr18)->requestToken('ck', 'cs', 'https://app.example/cb');
    }

    private function formResponse(int $status, string $body): ResponseInterface
    {
        return $this->factory
            ->createResponse($status)
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody($this->factory->createStream($body));
    }

    private function flow(FlowRecorder $psr18): OAuthFlow
    {
        return new OAuthFlow(
            signer: new OAuth1Signer(new MockClock('@1700000000'), new StaticNonceGenerator('nonce-1')),
            psr18: $psr18,
            requestFactory: $this->factory,
            configuration: new Configuration(),
        );
    }
}

final class FlowRecorder implements ClientInterface
{
    public ?RequestInterface $lastRequest = null;

    public function __construct(private readonly ResponseInterface $response)
    {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->lastRequest = $request;

        return $this->response;
    }
}
