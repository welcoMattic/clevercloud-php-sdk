<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V2;

use CleverCloud\Sdk\Resource\V2\BackupsResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(BackupsResource::class)]
final class BackupsResourceTest extends TestCase
{
    public function testListReturnsTypedBackups(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            [
                'backup_id' => 'b526231a',
                'entity_id' => 'b1eee3f6',
                'status' => 'Done',
                'creation_date' => '2026-10-31T00:08:55Z',
                'delete_at' => '2026-11-07T00:08:55Z',
                'filename' => 'postgresql_42-20261031000855.dump',
                'download_url' => 'https://bucket.example/b526231a.dump',
            ],
            ['backup_id' => 'bkp_2', 'creation_date' => '2026-11-01T03:53:01Z'],
        ]);

        $backups = $this->resource($response)->list('postgresql_42', 'orga_1');

        self::assertCount(2, $backups);
        self::assertSame('b526231a', $backups[0]->id);
        self::assertSame('b1eee3f6', $backups[0]->entityId);
        self::assertSame('Done', $backups[0]->status);
        self::assertSame('2026-10-31T00:08:55Z', $backups[0]->createdAt);
        self::assertSame('2026-11-07T00:08:55Z', $backups[0]->deleteAt);
        self::assertSame('postgresql_42-20261031000855.dump', $backups[0]->filename);
        self::assertSame('https://bucket.example/b526231a.dump', $backups[0]->downloadUrl);
        self::assertSame('bkp_2', $backups[1]->id);
        self::assertNull($backups[1]->status);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/backups/orga_1/postgresql_42',
            $response->getRequestUrl(),
        );
    }

    public function testListResolvesPersonalOrganisationForSelf(): void
    {
        $self = ResourceFactory::jsonResponse(200, ['id' => 'user_1']);
        $response = ResourceFactory::jsonResponse(200, []);

        $this->resource($self, $response)->list('redis_42');

        self::assertSame('https://api.clever-cloud.com/v2/self', $self->getRequestUrl());
        self::assertSame(
            'https://api.clever-cloud.com/v2/backups/user_1/redis_42',
            $response->getRequestUrl(),
        );
    }

    private function resource(MockResponse ...$responses): BackupsResource
    {
        return new BackupsResource(
            ResourceFactory::http(new MockHttpClient($responses)),
            ResourceFactory::mapper(),
        );
    }
}
