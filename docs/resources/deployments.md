# Deployments (`/v2/.../applications/{id}/deployments`)

Source: [`src/Resource/V2/DeploymentsResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V2/DeploymentsResource.php)

## Access

```php
$client->deployments
```

`?string $organisationId = null` → scopes to `/self` (your apps) when null.

## Methods

```php
public function list(string $applicationId, ?string $organisationId = null, ?int $limit = null, ?int $offset = null): list<Deployment>
public function get(string $applicationId, string $deploymentId, ?string $organisationId = null): Deployment
public function cancel(string $applicationId, string $deploymentId, ?string $organisationId = null): void
```

| Method | HTTP | Path |
| --- | --- | --- |
| `list()` | GET | `/v2/.../applications/{appId}/deployments` (query: `?limit&offset`) |
| `get()` | GET | `/v2/.../applications/{appId}/deployments/{deploymentId}` |
| `cancel()` | DELETE | `/v2/.../applications/{appId}/deployments/{deploymentId}` |

## `Deployment` DTO

See [`src/Model/Deployment.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Model/Deployment.php). Notable
fields include `id`, `action` (`DeploymentAction` enum), `state`
(`DeploymentState` enum), `commit`, `cause`, `date`.

To trigger a new deployment, use
[`$client->applications->deploy()`](applications.md#lifecycle) instead — the
deployments resource is read-only beyond cancelling.
