<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\Root;

use CleverCloud\Sdk\Exception\ApiException;
use CleverCloud\Sdk\Model\Enum\LogExportArchive;
use CleverCloud\Sdk\Model\Enum\LogExportFileFormat;
use CleverCloud\Sdk\Model\Enum\LogExportItemStatus;
use CleverCloud\Sdk\Model\Enum\LogExportKind;
use CleverCloud\Sdk\Model\Enum\LogExportStatus;
use CleverCloud\Sdk\Resource\Root\LogExportsResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(LogExportsResource::class)]
#[CoversClass(LogExportStatus::class)]
final class LogExportsResourceTest extends TestCase
{
    public function testListMapsPageAtApiRoot(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'items' => [self::export(['status' => 'DONE'])],
            'nextCursor' => 'cur_2',
        ]);

        $page = $this->resource($response)->list('orga_1');

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/organisations/orga_1/logs/exports', $response->getRequestUrl());
        self::assertCount(1, $page->items);
        self::assertSame('export_0b9a4ac8-5d0f-4a8e-9a3e-4d2f7f1c6b21', $page->items[0]->id);
        self::assertSame(LogExportStatus::Done, $page->items[0]->status);
        self::assertSame('cur_2', $page->nextCursor);
    }

    public function testListSendsLimitAndCursor(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['items' => [], 'nextCursor' => null]);

        $page = $this->resource($response)->list('orga_1', 50, 'cur_2');

        self::assertSame(
            'https://api.clever-cloud.com/organisations/orga_1/logs/exports?limit=50&cursor=cur_2',
            $response->getRequestUrl(),
        );
        self::assertSame([], $page->items);
        self::assertNull($page->nextCursor);
    }

    public function testCreatePostsMinimalBody(): void
    {
        $response = ResourceFactory::jsonResponse(202, self::export());

        $export = $this->resource($response)->create('orga_1', '2026-11-01T00:00:00Z');

        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/organisations/orga_1/logs/exports', $response->getRequestUrl());
        self::assertSame(
            '{"since":"2026-11-01T00:00:00Z","logs":true,"accesslogs":false}',
            $response->getRequestOptions()['body'],
        );
        self::assertSame(LogExportStatus::Pending, $export->status);
    }

    public function testCreatePostsEveryOption(): void
    {
        $response = ResourceFactory::jsonResponse(202, self::export());

        $this->resource($response)->create(
            'orga_1',
            '2026-11-01T00:00:00Z',
            until: '2026-11-02T00:00:00Z',
            logs: false,
            accessLogs: true,
            resourceIds: ['app_1', 'postgresql_2'],
            fileFormat: LogExportFileFormat::Json,
            archive: LogExportArchive::TarGz,
        );

        self::assertSame(
            '{"since":"2026-11-01T00:00:00Z","logs":false,"accesslogs":true,"until":"2026-11-02T00:00:00Z","resourceIds":["app_1","postgresql_2"],"fileFormat":"json","archive":"targz"}',
            $response->getRequestOptions()['body'],
        );
    }

    public function testGetMapsEveryField(): void
    {
        $response = ResourceFactory::jsonResponse(200, self::export([
            'status' => 'DONE',
            'accesslogs' => true,
            'resourceIds' => ['app_1'],
            'archiveSize' => 2048,
            'fileName' => 'export.tar.gz',
            'finishedAt' => '2026-11-01T00:05:00Z',
            'expiresAt' => '2026-11-08T00:05:00Z',
            'progress' => [
                'itemsTotal' => 2,
                'itemsDone' => 1,
                'itemsSkipped' => 1,
                'itemsFailed' => 0,
                'lineCount' => 1200,
                'readBytes' => 65536,
            ],
            'items' => [
                ['kind' => 'logs', 'resourceId' => 'app_1', 'status' => 'DONE', 'lineCount' => 1200, 'readBytes' => 65536, 'reason' => null],
                ['kind' => 'accesslogs', 'resourceId' => 'app_1', 'status' => 'SKIPPED', 'lineCount' => 0, 'readBytes' => 0, 'reason' => 'no access logs'],
            ],
        ]));

        $export = $this->resource($response)->get('orga_1', 'export_0b9a4ac8-5d0f-4a8e-9a3e-4d2f7f1c6b21');

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/organisations/orga_1/logs/exports/export_0b9a4ac8-5d0f-4a8e-9a3e-4d2f7f1c6b21',
            $response->getRequestUrl(),
        );
        self::assertSame('orga_1', $export->ownerId);
        self::assertSame('user_1', $export->requestedBy);
        self::assertTrue($export->logs);
        self::assertTrue($export->accessLogs);
        self::assertSame(['app_1'], $export->resourceIds);
        self::assertSame(LogExportFileFormat::Json, $export->fileFormat);
        self::assertSame(LogExportArchive::TarGz, $export->archive);
        self::assertSame('2026-11-01T00:00:00Z', $export->since);
        self::assertSame(2048, $export->archiveSize);
        self::assertSame('export.tar.gz', $export->fileName);
        self::assertSame('2026-11-08T00:05:00Z', $export->expiresAt);
        self::assertNotNull($export->progress);
        self::assertSame(2, $export->progress->itemsTotal);
        self::assertSame(65536, $export->progress->readBytes);
        self::assertCount(2, $export->items);
        self::assertSame(LogExportKind::Logs, $export->items[0]->kind);
        self::assertSame(LogExportItemStatus::Done, $export->items[0]->status);
        self::assertNull($export->items[0]->reason);
        self::assertSame(LogExportKind::AccessLogs, $export->items[1]->kind);
        self::assertSame(LogExportItemStatus::Skipped, $export->items[1]->status);
        self::assertSame('no access logs', $export->items[1]->reason);
    }

    public function testDeleteHitsExport(): void
    {
        $response = ResourceFactory::emptyResponse(204);

        $this->resource($response)->delete('orga_1', 'export_1');

        self::assertSame('DELETE', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/organisations/orga_1/logs/exports/export_1', $response->getRequestUrl());
    }

    public function testDownloadReturnsRawArchiveResponse(): void
    {
        $response = new MockResponse("\x1f\x8barchive-bytes", [
            'http_code' => 200,
            'response_headers' => ['content-type' => 'application/gzip'],
        ]);

        $download = $this->resource($response)->download('orga_1', 'export_1');

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/organisations/orga_1/logs/exports/export_1/download',
            $response->getRequestUrl(),
        );
        self::assertSame('Accept: */*', ResourceFactory::findHeader($response, 'Accept'));
        self::assertSame("\x1f\x8barchive-bytes", (string) $download->getBody());
        self::assertSame('application/gzip', $download->getHeaderLine('Content-Type'));
    }

    public function testDownloadSurfacesExportNotDone(): void
    {
        $response = ResourceFactory::jsonResponse(409, ['error' => 'export is not DONE']);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('export is not DONE');

        $this->resource($response)->download('orga_1', 'export_1');
    }

    public function testStatusTerminalCases(): void
    {
        self::assertTrue(LogExportStatus::Done->isTerminal());
        self::assertTrue(LogExportStatus::Failed->isTerminal());
        self::assertTrue(LogExportStatus::Cancelled->isTerminal());
        self::assertTrue(LogExportStatus::Expired->isTerminal());
        self::assertFalse(LogExportStatus::Pending->isTerminal());
        self::assertFalse(LogExportStatus::Running->isTerminal());
        self::assertFalse(LogExportStatus::Assembling->isTerminal());
        self::assertFalse(LogExportStatus::Unknown->isTerminal());
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private static function export(array $overrides = []): array
    {
        return array_replace([
            'id' => 'export_0b9a4ac8-5d0f-4a8e-9a3e-4d2f7f1c6b21',
            'status' => 'PENDING',
            'ownerId' => 'orga_1',
            'requestedBy' => 'user_1',
            'logs' => true,
            'accesslogs' => false,
            'resourceIds' => null,
            'fileFormat' => 'json',
            'archive' => 'targz',
            'since' => '2026-11-01T00:00:00Z',
            'until' => '2026-11-02T00:00:00Z',
            'archiveSize' => null,
            'fileName' => null,
            'error' => null,
            'createdAt' => '2026-11-01T00:00:00Z',
            'startedAt' => null,
            'finishedAt' => null,
            'expiresAt' => null,
            'progress' => ['itemsTotal' => 0, 'itemsDone' => 0, 'itemsSkipped' => 0, 'itemsFailed' => 0, 'lineCount' => 0, 'readBytes' => 0],
            'items' => [],
        ], $overrides);
    }

    private function resource(MockResponse $response): LogExportsResource
    {
        return new LogExportsResource(
            ResourceFactory::http(new MockHttpClient([$response])),
            ResourceFactory::mapper(),
        );
    }
}
