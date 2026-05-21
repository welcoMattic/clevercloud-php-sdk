# Orchestration (`/v4/orchestration/...`)

Source: [`src/Resource/V4/OrchestrationResource.php`](../../src/Resource/V4/OrchestrationResource.php)

Live runtime orchestration data — richer than the V2 equivalents
(`$client->applications->instances()`, `$client->deployments`).

## Access

```php
$client->orchestration
```

## Methods

```php
public function instances(string $applicationId, ?string $organisationId = null): list<array<string, mixed>>
public function deployments(string $applicationId, ?string $organisationId = null, ?int $limit = null, ?int $offset = null): list<Deployment>
public function getDeployment(string $applicationId, string $deploymentId, ?string $organisationId = null): Deployment
```

| Method | HTTP | Path |
| --- | --- | --- |
| `instances()` | GET | `/v4/orchestration/.../applications/{appId}/instances` (raw list) |
| `deployments()` | GET | `/v4/orchestration/.../applications/{appId}/deployments` (query: `?limit&offset`) |
| `getDeployment()` | GET | `/v4/orchestration/.../applications/{appId}/deployments/{deploymentId}` |

The instances response is intentionally left raw (`list<array<string, mixed>>`)
— orchestration metadata varies by runtime and isn't yet typed in a DTO.
