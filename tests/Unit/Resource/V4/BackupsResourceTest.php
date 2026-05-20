<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V4;

use CleverCloud\Sdk\Resource\V4\BackupsResource;
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
                'backup_id' => 'bkp_1',
                'created_at' => '2026-05-19T08:00:00Z',
                'status' => 'success',
                'download_url' => 'https://bucket.example/bkp_1.tar.gz',
                'size' => 12_345_678,
                'type' => 'full',
            ],
            ['backup_id' => 'bkp_2', 'created_at' => '2026-05-20T08:00:00Z'],
        ]);

        $backups = $this->resource($response)->list('postgresql-addon', 'addon_42');

        self::assertCount(2, $backups);
        self::assertSame('bkp_1', $backups[0]->id);
        self::assertSame('success', $backups[0]->status);
        self::assertSame('https://bucket.example/bkp_1.tar.gz', $backups[0]->downloadUrl);
        self::assertSame(12_345_678, $backups[0]->size);
        self::assertSame('bkp_2', $backups[1]->id);
        self::assertNull($backups[1]->status);
        self::assertSame(
            'https://api.clever-cloud.com/v4/addon-providers/postgresql-addon/addons/addon_42/backups',
            $response->getRequestUrl(),
        );
    }

    public function testGetReturnsSingleBackup(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'backup_id' => 'bkp_1',
            'created_at' => '2026-05-19T08:00:00Z',
        ]);

        $backup = $this->resource($response)->get('postgresql-addon', 'addon_42', 'bkp_1');

        self::assertSame('bkp_1', $backup->id);
        self::assertSame(
            'https://api.clever-cloud.com/v4/addon-providers/postgresql-addon/addons/addon_42/backups/bkp_1',
            $response->getRequestUrl(),
        );
    }

    public function testRestoreHitsPostEndpoint(): void
    {
        $response = ResourceFactory::jsonResponse(202, []);

        $this->resource($response)->restore('postgresql-addon', 'addon_42', 'bkp_1');

        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v4/addon-providers/postgresql-addon/addons/addon_42/backups/bkp_1/restore',
            $response->getRequestUrl(),
        );
    }

    private function resource(MockResponse $response): BackupsResource
    {
        return new BackupsResource(
            ResourceFactory::http(new MockHttpClient([$response])),
            ResourceFactory::mapper(),
        );
    }
}
