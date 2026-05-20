<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\Bridge;

use CleverCloud\Sdk\Resource\Bridge\ApiTokensResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(ApiTokensResource::class)]
final class ApiTokensResourceTest extends TestCase
{
    public function testListHitsApiBridge(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            ['id' => 'tok_1', 'name' => 'CI', 'scopes' => ['orga:read']],
            ['id' => 'tok_2', 'name' => 'Dashboard'],
        ]);

        $tokens = $this->resource($response)->list();

        self::assertCount(2, $tokens);
        self::assertSame('tok_1', $tokens[0]->id);
        self::assertSame('CI', $tokens[0]->name);
        self::assertSame(['orga:read'], $tokens[0]->scopes);
        self::assertSame('Dashboard', $tokens[1]->name);
        self::assertSame('https://api-bridge.clever-cloud.com/v2/api-tokens', $response->getRequestUrl());
    }

    public function testGetReturnsSingleToken(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'id' => 'tok_1',
            'name' => 'CI',
            'created_at' => '2026-01-01T00:00:00Z',
            'expires_at' => '2026-12-31T23:59:59Z',
            'last_used_at' => '2026-05-19T10:00:00Z',
        ]);

        $token = $this->resource($response)->get('tok_1');

        self::assertSame('tok_1', $token->id);
        self::assertSame('2026-01-01T00:00:00Z', $token->createdAt);
        self::assertSame('2026-12-31T23:59:59Z', $token->expiresAt);
        self::assertSame('2026-05-19T10:00:00Z', $token->lastUsedAt);
        self::assertSame('https://api-bridge.clever-cloud.com/v2/api-tokens/tok_1', $response->getRequestUrl());
    }

    public function testCreateReturnsTokenWithPlaintext(): void
    {
        $response = ResourceFactory::jsonResponse(201, [
            'id' => 'tok_new',
            'name' => 'Backup script',
            'token' => 'cc_secret_eyJhbGciOi...',
            'scopes' => ['addon:read', 'addon:backup:create'],
        ]);

        $token = $this->resource($response)->create([
            'name' => 'Backup script',
            'scopes' => ['addon:read', 'addon:backup:create'],
        ]);

        self::assertSame('tok_new', $token->id);
        self::assertSame('cc_secret_eyJhbGciOi...', $token->token);
        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame('https://api-bridge.clever-cloud.com/v2/api-tokens', $response->getRequestUrl());
        self::assertSame(
            '{"name":"Backup script","scopes":["addon:read","addon:backup:create"]}',
            $response->getRequestOptions()['body'],
        );
    }

    public function testUpdatePatchesJson(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['id' => 'tok_1', 'name' => 'renamed']);

        $this->resource($response)->update('tok_1', ['name' => 'renamed']);

        self::assertSame('PATCH', $response->getRequestMethod());
        self::assertSame('https://api-bridge.clever-cloud.com/v2/api-tokens/tok_1', $response->getRequestUrl());
        self::assertSame('{"name":"renamed"}', $response->getRequestOptions()['body']);
    }

    public function testDeleteHitsBridge(): void
    {
        $response = ResourceFactory::emptyResponse(204);

        $this->resource($response)->delete('tok_1');

        self::assertSame('DELETE', $response->getRequestMethod());
        self::assertSame('https://api-bridge.clever-cloud.com/v2/api-tokens/tok_1', $response->getRequestUrl());
    }

    private function resource(MockResponse $response): ApiTokensResource
    {
        return new ApiTokensResource(
            ResourceFactory::http(new MockHttpClient([$response])),
            ResourceFactory::mapper(),
        );
    }
}
