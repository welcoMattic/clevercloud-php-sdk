# Log exports (`/organisations/{orgId}/logs/exports`)

Source: [`src/Resource/Root/LogExportsResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/Root/LogExportsResource.php)

Asynchronous exports of an organisation's runtime logs and/or access logs over
a time window, delivered as a downloadable archive. These paths are
unversioned: they live at the API root (`Configuration::$rootBaseUrl`), not
under `/v2` or `/v4`.

## Access

```php
$client->logExports
```

## Methods

```php
public function list(string $organisationId, ?int $limit = null, ?string $cursor = null): LogExportPage
public function create(string $organisationId, string $since, ?string $until = null, bool $logs = true, bool $accessLogs = false, ?array $resourceIds = null, ?LogExportFileFormat $fileFormat = null, ?LogExportArchive $archive = null): LogExport
public function get(string $organisationId, string $exportId): LogExport
public function delete(string $organisationId, string $exportId): void
public function download(string $organisationId, string $exportId): ResponseInterface
```

| Method | HTTP | Path | Returns |
| --- | --- | --- | --- |
| `list()` | GET | `/organisations/{orgId}/logs/exports` | `LogExportPage` |
| `create()` | POST | `/organisations/{orgId}/logs/exports` | `LogExport` (in `PENDING`) |
| `get()` | GET | `/organisations/{orgId}/logs/exports/{exportId}` | `LogExport` |
| `delete()` | DELETE | `/organisations/{orgId}/logs/exports/{exportId}` | `void` |
| `download()` | GET | `/organisations/{orgId}/logs/exports/{exportId}/download` | PSR-7 `ResponseInterface` |

## Workflow

```php
use CleverCloud\Sdk\Model\Enum\LogExportStatus;

$export = $client->logExports->create(
    'orga_xxx',
    since: '2026-11-01T00:00:00Z',
    until: '2026-11-02T00:00:00Z',
    logs: true,
    accessLogs: true,
    resourceIds: ['app_xxx'],
);

while (!$export->status->isTerminal()) {
    sleep(5);
    $export = $client->logExports->get('orga_xxx', $export->id);
}

if (LogExportStatus::Done === $export->status) {
    $response = $client->logExports->download('orga_xxx', $export->id);
    $file = fopen($export->fileName ?? 'export.tar.gz', 'w');
    $body = $response->getBody();
    while (!$body->eof()) {
        fwrite($file, $body->read(1 << 20));
    }
    fclose($file);
}
```

- `create()`: at least one of `$logs` and `$accessLogs` must be `true`, and
  `$until` must be after `$since`. `$resourceIds` takes 1 to 2000 application
  ids (`app_…`) or add-on real ids (`postgresql_…`); an `addon_…` id is
  refused. `null` exports every resource of the organisation. The API answers
  409 when an equivalent export is already in flight and 429 past the
  organisation's limit of exports in flight.
- `$fileFormat` / `$archive`: only `json` in `targz` is produced today; any
  other value answers 501. Leave them `null` to let the API pick.
- `list()` is paginated: pass `$page->nextCursor` as `$cursor` to read the
  next page; it is `null` on the last one. Listed exports carry no `items`.
- `delete()` cancels an export in flight, or deletes the archive of a finished
  one. The export stays listed as `CANCELLED` until it expires.

## Downloading the archive

The download endpoint answers with a 302 to a short-lived presigned URL. The
SDK's Symfony HttpClient follows that redirect and drops the `Authorization`
header on the cross-host hop, as the presigned URL requires, so `download()`
returns the archive response itself. Stream its body to disk rather than
casting it to a string. The API answers 409 while the export is not `DONE` and
410 once its archive has expired; both raise an `ApiException`.

## `LogExport` DTO

```php
public string $id;
public LogExportStatus $status;
public ?string $ownerId;
public ?string $requestedBy;
public bool $logs;
public bool $accessLogs; // `accesslogs` on the wire
public ?array $resourceIds; // null when the whole organisation was exported
public ?LogExportFileFormat $fileFormat;
public ?LogExportArchive $archive;
public ?string $since;
public ?string $until;
public ?int $archiveSize; // bytes, once DONE
public ?string $fileName; // once DONE
public ?string $error; // why the export failed
public ?string $createdAt;
public ?string $startedAt;
public ?string $finishedAt;
public ?string $expiresAt;
public ?LogExportProgress $progress;
public array $items; // list<LogExportItem>, empty in a listing
```

`LogExportProgress` carries `itemsTotal`, `itemsDone`, `itemsSkipped`,
`itemsFailed`, `lineCount` and `readBytes`. Each `LogExportItem` is one
(resource, kind) pair: `resourceId`, `kind` (`LogExportKind`), `status`
(`LogExportItemStatus`), `lineCount`, `readBytes`, `reason`.

## Enums

- `LogExportStatus`: `PENDING`, `RUNNING`, `ASSEMBLING`, `DONE`, `FAILED`,
  `CANCELLED`, `EXPIRED`, `UNKNOWN`. `isTerminal()` is true for `DONE`,
  `FAILED`, `CANCELLED` and `EXPIRED`.
- `LogExportItemStatus`: `PENDING`, `RUNNING`, `DONE`, `FAILED`, `SKIPPED`,
  `UNKNOWN`.
- `LogExportKind`: `logs`, `accesslogs`, `unknown`.
- `LogExportFileFormat`: `json`, `csv`, `avro`, `parquet`.
- `LogExportArchive`: `targz`, `tarzst`, `zip`, `none`.
