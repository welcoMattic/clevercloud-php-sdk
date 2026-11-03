# Orchestration (`/v4/orchestration/...`)

Source: [`src/Resource/V4/OrchestrationResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V4/OrchestrationResource.php)

Live runtime orchestration data — richer than the V2 equivalents
(`$client->applications->instances()`, `$client->deployments`).

## Access

```php
$client->orchestration
```

Paths are scoped to `/organisations/{id}`. A null `?string $organisationId`
targets your personal space, whose `user_...` id is read from `GET /v2/self`
first (V4 has no `/self` variant).

## Methods

```php
public function instances(string $applicationId, ?string $organisationId = null): list<array<string, mixed>>
public function deployments(string $applicationId, ?string $organisationId = null, ?int $limit = null, ?int $offset = null): list<OrchestrationDeployment>
public function getDeployment(string $applicationId, string $deploymentId, ?string $organisationId = null): OrchestrationDeployment
```

| Method | HTTP | Path |
| --- | --- | --- |
| `instances()` | GET | `/v4/orchestration/.../applications/{appId}/instances` (raw list) |
| `deployments()` | GET | `/v4/orchestration/.../applications/{appId}/deployments` (query: `?limit&offset`) |
| `getDeployment()` | GET | `/v4/orchestration/.../applications/{appId}/deployments/{deploymentId}` |

The instances response is intentionally left raw (`list<array<string, mixed>>`)
— orchestration metadata varies by runtime and isn't yet typed in a DTO.

## `OrchestrationDeployment` DTO

The V4 deployment shape differs from the V2 `Deployment`, so it has its own
DTO: `id`, `ownerId`, `applicationId`, `startDate` (RFC 3339), `state`
(`QUEUED`, `WORK_IN_PROGRESS`, `TASK_IN_PROGRESS`, `FAILED`, `CANCELLED`,
`SUCCEEDED`), `steps` (list of `OrchestrationDeploymentStep` with `state` and
`date`, oldest first), `version` (`commitId`, `previousCommitId`), `origin`
(`action`, `cause`, `source`, `authorId`, `constraints`, `priority`) and
`hasDedicatedBuild`. See
[`src/Model/OrchestrationDeployment.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Model/OrchestrationDeployment.php).
