# Kubernetes (`/v4/kubernetes/...`)

Source: [`src/Resource/V4/KubernetesResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V4/KubernetesResource.php)

Clever Kubernetes Engine: clusters, node groups, standalone nodes,
kubeconfig, upgrades, quota and usage.

## Access

```php
$client->kubernetes
```

The Kubernetes API has no `/self` variant: every owner-scoped method takes
the owner (organisation or user) id as its first argument, `$ownerId`. Paths
below are relative to `/v4/kubernetes/organisations/{ownerId}`.

Mutations are asynchronous. They return the resource in a transitional
status (`DEPLOYING`, `DELETING`, `TERMINATING`, `RECONCILING`, ...) and the
platform converges in the background: poll `getCluster()` or
`getNodeGroup()` to follow the progress.

```php
use CleverCloud\Sdk\Model\Enum\KubernetesNodeFlavor;

$cluster = $client->kubernetes->createCluster('orga_xxx', 'prod', version: '1.35', zone: 'par');
$client->kubernetes->createNodeGroup('orga_xxx', $cluster->id, 'workers', KubernetesNodeFlavor::M, targetNodeCount: 3);

file_put_contents('kubeconfig.yaml', $client->kubernetes->kubeconfig('orga_xxx', $cluster->id));
```

## Clusters

```php
public function listClusters(string $ownerId, list<KubernetesClusterStatus> $statuses = []): list<KubernetesCluster>
public function getCluster(string $ownerId, string $kubernetesId): KubernetesCluster
public function createCluster(string $ownerId, string $name, ?string $description = null, ?string $version = null, ?string $zoneId = null, ?string $zone = null, ?string $deploymentProfileId = null, ?string $networkGroupId = null, ?list<string> $tags = null, ?KubernetesClusterFeatures $features = null, ?array $topologyConfig = null, ?list<array> $nodeGroups = null): KubernetesCluster
public function updateCluster(string $ownerId, string $kubernetesId, ?string $name = null, ?string $description = null, ?list<string> $tags = null, ?KubernetesClusterFeatures $features = null): KubernetesCluster
public function deleteCluster(string $ownerId, string $kubernetesId): KubernetesCluster
```

| Method | HTTP | Path | Body |
| --- | --- | --- | --- |
| `listClusters()` | GET | `/clusters?status=...` | - |
| `getCluster()` | GET | `/clusters/{kubernetesId}` | - |
| `createCluster()` | POST | `/clusters` | `{"name": "...", ...}` |
| `updateCluster()` | PATCH | `/clusters/{kubernetesId}` | `{"name"?, "description"?, "tags"?, "features"?}` |
| `deleteCluster()` | DELETE | `/clusters/{kubernetesId}` | - |

- `listClusters()` without `$statuses` returns every cluster but the
  `DELETED` ones. Each status is sent as a repeated `status` query parameter.
- `getCluster()` is the only call that fills `apiServerUrl`,
  `loadBalancers`, `nodeGroups` and `standaloneNodeGroups`; list and mutation
  responses leave them empty.
- `createCluster()` and `updateCluster()` only send the arguments you pass
  (non-null). Without `$deploymentProfileId`, `$zoneId` or `$zone`, the API
  picks the default profile of the only zone the owner may use. Prefer
  `$zoneId` over `$zone`: zone names are not unique. `$topologyConfig` is
  tagged by `topology`, for instance
  `['topology' => 'ALL_IN_ONE', 'flavor' => 'M', 'replicationFactor' => 1]`;
  `serviceConfig()` lists the flavors and replication factors each topology
  accepts. `$nodeGroups` entries take the same fields as the
  `createNodeGroup()` body.
- `updateCluster()` merges `$features` onto the current feature flags, and
  only its non-null fields are sent. Feature changes are applied in the
  background, so the returned `features` reports what is installed.
- `deleteCluster()` returns the cluster with the `DELETING` status.

## Cluster operations

```php
public function redeploy(string $ownerId, string $kubernetesId, ?list<string> $beforeYamls = null, ?list<string> $afterYamls = null, ?KubernetesDrainPolicy $drainPolicy = null): KubernetesCluster
public function resume(string $ownerId, string $kubernetesId, ?KubernetesDrainPolicy $drainPolicy = null): KubernetesCluster
public function reconcilePlugins(string $ownerId, string $kubernetesId): KubernetesCluster
public function migrateSni(string $ownerId, string $kubernetesId): KubernetesCluster
public function checkVersion(string $ownerId, string $kubernetesId, ?bool $workers = null): KubernetesVersionCheck
public function updateVersion(string $ownerId, string $kubernetesId, string $targetVersion, ?KubernetesOperationScope $scope = null, ?KubernetesDrainPolicy $drainPolicy = null): KubernetesCluster
/** @deprecated */
public function enableCephCsi(string $ownerId, string $kubernetesId): KubernetesCluster
```

| Method | HTTP | Path | Body |
| --- | --- | --- | --- |
| `redeploy()` | POST | `/clusters/{kubernetesId}/redeploy` | `{"beforeYamls"?, "afterYamls"?, "drainPolicy"?}` or none |
| `resume()` | POST | `/clusters/{kubernetesId}/resume` | `{"drainPolicy": "..."}` or none |
| `reconcilePlugins()` | POST | `/clusters/{kubernetesId}/plugins/reconcile` | - |
| `migrateSni()` | POST | `/clusters/{kubernetesId}/sni-migration` | - |
| `checkVersion()` | GET | `/clusters/{kubernetesId}/version/check?workers=true` | - |
| `updateVersion()` | POST | `/clusters/{kubernetesId}/version/update` | `{"targetVersion": "...", "scope"?, "drainPolicy"?}` |
| `enableCephCsi()` | POST | `/clusters/{kubernetesId}/csi/ceph` | - |

- `redeploy()` replaces the control plane and rolls the worker nodes.
  `$beforeYamls` / `$afterYamls` are base64-encoded YAML manifests applied
  before and after the control plane switch. Without any argument, no body
  is sent.
- `resume()` retries the operation that left the cluster `FAILED`.
- `reconcilePlugins()` installs the plugins the cluster's features need and
  removes the unneeded ones, without touching any node.
- `migrateSni()` moves the API server access to `https://<cluster domain>`
  on port 443. Every kubeconfig downloaded before stops working once it
  completes.
- `checkVersion()` with `$workers = true` also probes the kubelet version of
  each node: `workerVersions` and `workersInSync` stay `null` otherwise.
- `updateVersion()` without `$scope` upgrades the control plane, then every
  node group.
- `$drainPolicy`: `STRICT` (the API default) never deletes a node that did
  not drain and fails the operation instead; `FORCE` deletes it after a
  shorter wait.
- `enableCephCsi()` is deprecated by the API: use
  `updateCluster(..., features: new KubernetesClusterFeatures(csi: true))`.

## Kubeconfig

```php
public function kubeconfig(string $ownerId, string $kubernetesId, ?KubernetesKubeconfigType $type = null, ?string $authorization = null): string
public function kubeconfigPresignedUrl(string $ownerId, string $kubernetesId): string
```

| Method | HTTP | Path |
| --- | --- | --- |
| `kubeconfig()` | GET | `/clusters/{kubernetesId}/kubeconfig.yaml?type=...&authorization=...` |
| `kubeconfigPresignedUrl()` | GET | `/clusters/{kubernetesId}/kubeconfig/presigned-url` |

`kubeconfig()` returns the raw YAML body as a string (`Accept: text/plain`).
`$type` picks how `kubectl` reaches the API server: `LOADBALANCER` (the API
default), `NETWORKGROUP` or `APISERVER_IP`. `kubeconfigPresignedUrl()`
returns a URL that downloads the kubeconfig without other credentials; its
token can also be passed to `kubeconfig()` as `$authorization`.

## Events

```php
public function events(string $ownerId, string $kubernetesId, ?int $limit = null): list<array<string, mixed>>
public function deploymentEvents(string $ownerId, string $kubernetesId, ?int $limit = null): list<KubernetesDeploymentEvent>
```

| Method | HTTP | Path |
| --- | --- | --- |
| `events()` | GET | `/clusters/{kubernetesId}/events?limit=...` |
| `deploymentEvents()` | GET | `/clusters/{kubernetesId}/deployment-events?limit=...` |

`$limit` defaults to 50 server-side, at most 1000. `events()` returns raw
arrays because each entry's shape depends on its `event` discriminator:
`CLUSTER_STATUS` (status change, with `failure` when it failed),
`CLUSTER_ITEM` (infrastructure item created or removed) or `NODE_LIFECYCLE`
(node ready, failed or deleted). `deploymentEvents()` returns the steps of
the deployment operations as typed `KubernetesDeploymentEvent` objects.

## Node groups

```php
public function listNodeGroups(string $ownerId, string $kubernetesId, list<KubernetesNodeGroupStatus> $statuses = []): list<KubernetesNodeGroup>
public function getNodeGroup(string $ownerId, string $kubernetesId, string $nodeGroupId): KubernetesNodeGroup
public function createNodeGroup(string $ownerId, string $kubernetesId, string $name, KubernetesNodeFlavor $flavor, int $targetNodeCount, ?int $minNodeCount = null, ?int $maxNodeCount = null, ?bool $autoscalingEnabled = null, ?string $description = null, ?string $tag = null, ?array<string, string> $labels = null, ?list<KubernetesTaint> $taints = null): KubernetesNodeGroup
public function updateNodeGroup(string $ownerId, string $kubernetesId, string $nodeGroupId, string $name, int $targetNodeCount, ?int $minNodeCount = null, ?int $maxNodeCount = null, ?bool $autoscalingEnabled = null, ?string $description = null, ?string $tag = null, ?KubernetesDrainPolicy $drainPolicy = null): KubernetesNodeGroup
public function deleteNodeGroup(string $ownerId, string $kubernetesId, string $nodeGroupId): KubernetesNodeGroup
public function resumeNodeGroup(string $ownerId, string $kubernetesId, string $nodeGroupId): KubernetesNodeGroup
```

| Method | HTTP | Path | Body |
| --- | --- | --- | --- |
| `listNodeGroups()` | GET | `/clusters/{kubernetesId}/node-groups?status=...` | - |
| `getNodeGroup()` | GET | `/clusters/{kubernetesId}/node-groups/{nodeGroupId}` | - |
| `createNodeGroup()` | POST | `/clusters/{kubernetesId}/node-groups` | `{"name", "flavor", "targetNodeCount", ...}` |
| `updateNodeGroup()` | PATCH | `/clusters/{kubernetesId}/node-groups/{nodeGroupId}` | `{"name", "targetNodeCount", ...}` |
| `deleteNodeGroup()` | DELETE | `/clusters/{kubernetesId}/node-groups/{nodeGroupId}` | - |
| `resumeNodeGroup()` | POST | `/clusters/{kubernetesId}/node-groups/{nodeGroupId}/resume` | - |

- Names are lowercase RFC 1123 subdomains of at most 55 characters. Node
  counts range from 0 to 256.
- `updateNodeGroup()` always sends `name` and `targetNodeCount` (required by
  the API). Flavor, taints and labels cannot change after creation. A
  `$drainPolicy` decides what a scale-down does with nodes that will not
  drain.
- `deleteNodeGroup()` returns the group with the `TERMINATING` status.
- `resumeNodeGroup()` resumes a group stuck in `DEPLOYING`; a `FAILED` group
  is retried automatically.

## Standalone nodes

```php
public function listNodes(string $ownerId, string $kubernetesId): list<KubernetesNode>
public function getNode(string $ownerId, string $kubernetesId, string $nodeId): KubernetesNode
public function createNode(string $ownerId, string $kubernetesId, string $name, KubernetesNodeFlavor $flavor, ?string $description = null, ?string $tag = null, ?array<string, string> $labels = null, ?list<KubernetesTaint> $taints = null): KubernetesNode
public function deleteNode(string $ownerId, string $kubernetesId, string $nodeId): KubernetesNode
```

| Method | HTTP | Path | Body |
| --- | --- | --- | --- |
| `listNodes()` | GET | `/clusters/{kubernetesId}/nodes` | - |
| `getNode()` | GET | `/clusters/{kubernetesId}/nodes/{nodeId}` | - |
| `createNode()` | POST | `/clusters/{kubernetesId}/nodes` | `{"name", "flavor", ...}` |
| `deleteNode()` | DELETE | `/clusters/{kubernetesId}/nodes/{nodeId}` | - |

A standalone node lives outside any node group.

## Owner and platform

```php
public function quota(string $ownerId): array
public function usage(string $ownerId): list<array<string, mixed>>
public function zones(string $ownerId): list<KubernetesOwnerZone>
public function serviceConfig(): array
public function pluginCatalog(): array
```

| Method | HTTP | Path |
| --- | --- | --- |
| `quota()` | GET | `/v4/kubernetes/organisations/{ownerId}/quota` |
| `usage()` | GET | `/v4/kubernetes/organisations/{ownerId}/usage` |
| `zones()` | GET | `/v4/kubernetes/organisations/{ownerId}/zones` |
| `serviceConfig()` | GET | `/v4/kubernetes-product` |
| `pluginCatalog()` | GET | `/v4/kubernetes/plugins/catalog` |

- `quota()` returns `{id, tenantId, tags, quotas}`; each quota is tagged by
  `type`: `MillivCPUMaxLimit` (`maximum`, in millicores) or `RamMaxUsage`
  (`information`).
- `usage()` returns the CPU and RAM each active control plane or node group
  uses, as checked against the quota.
- `zones()` lists the zones where the owner may create a cluster, with the
  deployment profiles it may use there. Use `$client->zones` for the full
  zone details.
- `serviceConfig()` returns the available topologies (with their flavors and
  replication factors) and Kubernetes versions.
- `pluginCatalog()` returns the plugins installed per Kubernetes version and
  the migration strategies between versions.

## DTOs

All under `CleverCloud\Sdk\Model`, enums under `CleverCloud\Sdk\Model\Enum`.

| Class | Main fields |
| --- | --- |
| `KubernetesCluster` | `id`, `tenantId`, `name`, `status` (`KubernetesClusterStatus`), `version`, `creationDate`, `tags`, `topologyConfig` (raw array), `features`, `failure`, `waiting` (raw array), `apiServerUrl`, `zoneId` (`locationId` is its deprecated alias), `loadBalancers`, `nodeGroups`, `standaloneNodeGroups` |
| `KubernetesClusterFeatures` | `autoscalingEnabled`, `csi`, `nodeAutoprovisioning`, `registries` |
| `KubernetesClusterNodeGroup` | `id`, `name`, `status`, `flavor`, `currentNodeCount`, `targetNodeCount`, `failure`, `nodes` (`KubernetesClusterNode`) |
| `KubernetesClusterNode` | `id`, `name`, `status` (`KubernetesNodeStatus`), `vCpus`, `ramBytes` |
| `KubernetesLoadBalancer` | `id`, `flavor`, `domainName`, `ips` |
| `KubernetesNodeGroup` | `id`, `clusterId`, `name`, `flavor` (`KubernetesNodeFlavor`), `status` (`KubernetesNodeGroupStatus`), node counts, `autoscalingEnabled`, `labels`, `taints`, `failure`, `createdAt`, `updatedAt` |
| `KubernetesNode` | `id`, `clusterId`, `name`, `flavor`, `status`, `labels`, `taints`, `description`, `tag` |
| `KubernetesTaint` | `key`, `effect` (`KubernetesTaintEffect`), `value`; also used as input |
| `KubernetesFailure` | `operation`, `message`, `occurredAt`, `step`, `undrainedNodes` (raw arrays, node groups only) |
| `KubernetesDeploymentEvent` | `operation`, `status`, `createdAt`, `stepName`, `nodeGroupId`, `detail` |
| `KubernetesVersionCheck` | `installed`, `latest`, `needUpdate`, `available`, `workerVersions`, `workersInSync` |
| `KubernetesOwnerZone` | `id`, `name`, `deploymentProfiles` |

Dates are kept as RFC 3339 strings. Versions are `MAJOR.MINOR` or
`MAJOR.MINOR.PATCH` strings.
