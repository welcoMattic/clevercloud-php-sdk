<?php

namespace CleverCloud\Sdk\Resource\V4;

use CleverCloud\Sdk\Exception\JsonException;
use CleverCloud\Sdk\Model\Enum\KubernetesClusterStatus;
use CleverCloud\Sdk\Model\Enum\KubernetesDrainPolicy;
use CleverCloud\Sdk\Model\Enum\KubernetesKubeconfigType;
use CleverCloud\Sdk\Model\Enum\KubernetesNodeFlavor;
use CleverCloud\Sdk\Model\Enum\KubernetesNodeGroupStatus;
use CleverCloud\Sdk\Model\Enum\KubernetesOperationScope;
use CleverCloud\Sdk\Model\KubernetesCluster;
use CleverCloud\Sdk\Model\KubernetesClusterFeatures;
use CleverCloud\Sdk\Model\KubernetesDeploymentEvent;
use CleverCloud\Sdk\Model\KubernetesNode;
use CleverCloud\Sdk\Model\KubernetesNodeGroup;
use CleverCloud\Sdk\Model\KubernetesOwnerZone;
use CleverCloud\Sdk\Model\KubernetesTaint;
use CleverCloud\Sdk\Model\KubernetesVersionCheck;
use CleverCloud\Sdk\Resource\AbstractV4Resource;

/**
 * Manages Clever Kubernetes Engine clusters, their node groups and standalone
 * nodes under `/v4/kubernetes/organisations/{ownerId}`.
 *
 * The API has no `/self` variant: every owner-scoped method takes the owner
 * (organisation or user) id as its first argument.
 *
 * Mutations are asynchronous: they return the resource in its transitional
 * status (`DEPLOYING`, `TERMINATING`, ...) and the platform converges in the
 * background. Poll `getCluster()` / `getNodeGroup()` to follow the progress.
 */
final readonly class KubernetesResource extends AbstractV4Resource
{
    /**
     * Returns the Kubernetes service configuration: the available control-plane
     * topologies with their flavors and replication factors, and the
     * available Kubernetes versions.
     *
     * @return array<int|string, mixed> `{topologies: list<...>, versions: {available: list<string>, default: string}}`
     */
    public function serviceConfig(): array
    {
        return $this->httpGet('/kubernetes-product');
    }

    /**
     * Returns the plugins the platform installs, per Kubernetes version, and
     * the migration strategies between versions.
     *
     * @return array<int|string, mixed> `{bases: list<...>, migrations: list<...>}`
     */
    public function pluginCatalog(): array
    {
        return $this->httpGet('/kubernetes/plugins/catalog');
    }

    /**
     * Lists the owner's clusters. Without `$statuses`, every cluster but the
     * `DELETED` ones is returned.
     *
     * @param list<KubernetesClusterStatus> $statuses
     *
     * @return list<KubernetesCluster>
     */
    public function listClusters(string $ownerId, array $statuses = []): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet(
            $this->ownerBase($ownerId).'/clusters',
            ['query' => ['status' => array_map(static fn (KubernetesClusterStatus $s): string => $s->value, $statuses)]],
        );

        return $this->mapCollection(KubernetesCluster::class, $payload);
    }

    /**
     * Returns one cluster, including its load balancers, node groups and
     * standalone node groups (only filled on this detail response).
     */
    public function getCluster(string $ownerId, string $kubernetesId): KubernetesCluster
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet($this->clusterPath($ownerId, $kubernetesId));

        return $this->mapTo(KubernetesCluster::class, $payload);
    }

    /**
     * Creates a cluster, with optional initial node groups, provisioned
     * asynchronously.
     *
     * When neither `$deploymentProfileId`, `$zoneId` nor `$zone` is given,
     * the API picks the default profile of the only zone the owner may use.
     *
     * @param string|null                     $version        Kubernetes version (`MAJOR.MINOR`); the service default when null
     * @param string|null                     $zone           zone name (`par`); prefer `$zoneId`, names are not unique
     * @param list<string>|null               $tags           semantic tags (`tag` or `key:value`)
     * @param array<string, mixed>|null       $topologyConfig control-plane topology, e.g.
     *                                                        `['topology' => 'ALL_IN_ONE', 'flavor' => 'M', 'replicationFactor' => 1]`
     * @param list<array<string, mixed>>|null $nodeGroups     initial node groups, each shaped like the
     *                                                        {@see createNodeGroup()} body
     */
    public function createCluster(
        string $ownerId,
        string $name,
        ?string $description = null,
        ?string $version = null,
        ?string $zoneId = null,
        ?string $zone = null,
        ?string $deploymentProfileId = null,
        ?string $networkGroupId = null,
        ?array $tags = null,
        ?KubernetesClusterFeatures $features = null,
        ?array $topologyConfig = null,
        ?array $nodeGroups = null,
    ): KubernetesCluster {
        $body = self::withoutNulls([
            'name' => $name,
            'description' => $description,
            'version' => $version,
            'zoneId' => $zoneId,
            'zone' => $zone,
            'deploymentProfileId' => $deploymentProfileId,
            'networkGroupId' => $networkGroupId,
            'tags' => $tags,
            'features' => null !== $features ? (object) self::featuresBody($features) : null,
            'topologyConfig' => $topologyConfig,
            'nodeGroups' => $nodeGroups,
        ]);

        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost($this->ownerBase($ownerId).'/clusters', ['json' => $body]);

        return $this->mapTo(KubernetesCluster::class, $payload);
    }

    /**
     * Updates the cluster's metadata and features. Only the arguments you
     * pass are sent; `$features` is merged onto the current feature flags,
     * and only its non-null fields are sent. Feature changes are applied in
     * the background, so the returned `features` reflects what is installed,
     * not what was just requested.
     *
     * @param list<string>|null $tags replaces the current tags
     */
    public function updateCluster(
        string $ownerId,
        string $kubernetesId,
        ?string $name = null,
        ?string $description = null,
        ?array $tags = null,
        ?KubernetesClusterFeatures $features = null,
    ): KubernetesCluster {
        $body = self::withoutNulls([
            'name' => $name,
            'description' => $description,
            'tags' => $tags,
            'features' => null !== $features ? (object) self::featuresBody($features) : null,
        ]);

        /** @var array<string, mixed> $payload */
        $payload = $this->httpPatch(
            $this->clusterPath($ownerId, $kubernetesId),
            ['json' => (object) $body],
        );

        return $this->mapTo(KubernetesCluster::class, $payload);
    }

    /**
     * Marks the cluster for deletion and returns it with the `DELETING`
     * status; its resources are removed asynchronously.
     */
    public function deleteCluster(string $ownerId, string $kubernetesId): KubernetesCluster
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpDelete($this->clusterPath($ownerId, $kubernetesId));

        return $this->mapTo(KubernetesCluster::class, $payload);
    }

    /**
     * Enables the Ceph CSI storage driver of a cluster.
     *
     * @deprecated deprecated by the API: use `updateCluster()` with
     *             `new KubernetesClusterFeatures(csi: true)` instead
     */
    public function enableCephCsi(string $ownerId, string $kubernetesId): KubernetesCluster
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost($this->clusterPath($ownerId, $kubernetesId).'/csi/ceph');

        return $this->mapTo(KubernetesCluster::class, $payload);
    }

    /**
     * Starts a redeployment of the control plane and a roll of the worker
     * nodes.
     *
     * @param list<string>|null $beforeYamls base64-encoded YAML manifests applied before the control plane is replaced
     * @param list<string>|null $afterYamls  base64-encoded YAML manifests applied after the control plane is replaced
     */
    public function redeploy(
        string $ownerId,
        string $kubernetesId,
        ?array $beforeYamls = null,
        ?array $afterYamls = null,
        ?KubernetesDrainPolicy $drainPolicy = null,
    ): KubernetesCluster {
        $body = self::withoutNulls([
            'beforeYamls' => $beforeYamls,
            'afterYamls' => $afterYamls,
            'drainPolicy' => $drainPolicy?->value,
        ]);

        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost(
            $this->clusterPath($ownerId, $kubernetesId).'/redeploy',
            [] === $body ? [] : ['json' => $body],
        );

        return $this->mapTo(KubernetesCluster::class, $payload);
    }

    /**
     * Retries the operation that left the cluster `FAILED` (deployment,
     * redeployment, upgrade or plugin reconcile).
     */
    public function resume(
        string $ownerId,
        string $kubernetesId,
        ?KubernetesDrainPolicy $drainPolicy = null,
    ): KubernetesCluster {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost(
            $this->clusterPath($ownerId, $kubernetesId).'/resume',
            null === $drainPolicy ? [] : ['json' => ['drainPolicy' => $drainPolicy->value]],
        );

        return $this->mapTo(KubernetesCluster::class, $payload);
    }

    /**
     * Installs every plugin the cluster's features need at its current
     * version and removes the unneeded ones, without touching any node.
     * Returns the cluster with the `RECONCILING` status.
     */
    public function reconcilePlugins(string $ownerId, string $kubernetesId): KubernetesCluster
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost($this->clusterPath($ownerId, $kubernetesId).'/plugins/reconcile');

        return $this->mapTo(KubernetesCluster::class, $payload);
    }

    /**
     * Moves the cluster's API server access to its own domain
     * (`https://<cluster domain>` on port 443). Once done, every kubeconfig
     * downloaded before stops working and must be downloaded again.
     */
    public function migrateSni(string $ownerId, string $kubernetesId): KubernetesCluster
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost($this->clusterPath($ownerId, $kubernetesId).'/sni-migration');

        return $this->mapTo(KubernetesCluster::class, $payload);
    }

    /**
     * Returns the installed, latest and available Kubernetes versions of the
     * cluster.
     *
     * @param bool|null $workers also probe the kubelet version each node runs
     */
    public function checkVersion(string $ownerId, string $kubernetesId, ?bool $workers = null): KubernetesVersionCheck
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet(
            $this->clusterPath($ownerId, $kubernetesId).'/version/check',
            ['query' => ['workers' => null === $workers ? null : ($workers ? 'true' : 'false')]],
        );

        return $this->mapTo(KubernetesVersionCheck::class, $payload);
    }

    /**
     * Starts an upgrade of the cluster to `$targetVersion`.
     *
     * @param KubernetesOperationScope|null $scope `ALL` (control plane then node groups) when null
     */
    public function updateVersion(
        string $ownerId,
        string $kubernetesId,
        string $targetVersion,
        ?KubernetesOperationScope $scope = null,
        ?KubernetesDrainPolicy $drainPolicy = null,
    ): KubernetesCluster {
        $body = self::withoutNulls([
            'targetVersion' => $targetVersion,
            'scope' => $scope?->value,
            'drainPolicy' => $drainPolicy?->value,
        ]);

        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost(
            $this->clusterPath($ownerId, $kubernetesId).'/version/update',
            ['json' => $body],
        );

        return $this->mapTo(KubernetesCluster::class, $payload);
    }

    /**
     * Downloads the cluster's kubeconfig, as raw YAML.
     *
     * @param KubernetesKubeconfigType|null $type          `LOADBALANCER` when null
     * @param string|null                   $authorization presigned token from {@see kubeconfigPresignedUrl()},
     *                                                     sent as the `authorization` query parameter
     */
    public function kubeconfig(
        string $ownerId,
        string $kubernetesId,
        ?KubernetesKubeconfigType $type = null,
        ?string $authorization = null,
    ): string {
        $response = $this->httpStream(
            'GET',
            $this->clusterPath($ownerId, $kubernetesId).'/kubeconfig.yaml',
            [
                'query' => ['type' => $type?->value, 'authorization' => $authorization],
                'headers' => ['Accept' => 'text/plain'],
            ],
        );

        return (string) $response->getBody();
    }

    /**
     * Returns a presigned URL to download the cluster's kubeconfig without
     * other credentials.
     *
     * @throws JsonException when the response carries no `url`
     */
    public function kubeconfigPresignedUrl(string $ownerId, string $kubernetesId): string
    {
        $payload = $this->httpGet($this->clusterPath($ownerId, $kubernetesId).'/kubeconfig/presigned-url');
        if (!isset($payload['url']) || !\is_string($payload['url'])) {
            throw new JsonException('Presigned kubeconfig response has no "url" string');
        }

        return $payload['url'];
    }

    /**
     * Returns the cluster's event log: status changes (`CLUSTER_STATUS`),
     * infrastructure items created or removed (`CLUSTER_ITEM`) and node
     * lifecycle events (`NODE_LIFECYCLE`). Entries are left raw because their
     * shape depends on the `event` discriminator.
     *
     * @param int|null $limit maximum number of events (API default 50, max 1000)
     *
     * @return list<array<string, mixed>>
     */
    public function events(string $ownerId, string $kubernetesId, ?int $limit = null): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet(
            $this->clusterPath($ownerId, $kubernetesId).'/events',
            ['query' => ['limit' => $limit]],
        );

        return $payload;
    }

    /**
     * Returns the steps of the cluster's deployment operations.
     *
     * @param int|null $limit maximum number of events (API default 50, max 1000)
     *
     * @return list<KubernetesDeploymentEvent>
     */
    public function deploymentEvents(string $ownerId, string $kubernetesId, ?int $limit = null): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet(
            $this->clusterPath($ownerId, $kubernetesId).'/deployment-events',
            ['query' => ['limit' => $limit]],
        );

        return $this->mapCollection(KubernetesDeploymentEvent::class, $payload);
    }

    /**
     * Lists the cluster's node groups. Without `$statuses`, every node group
     * but the `DELETED` ones is returned.
     *
     * @param list<KubernetesNodeGroupStatus> $statuses
     *
     * @return list<KubernetesNodeGroup>
     */
    public function listNodeGroups(string $ownerId, string $kubernetesId, array $statuses = []): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet(
            $this->clusterPath($ownerId, $kubernetesId).'/node-groups',
            ['query' => ['status' => array_map(static fn (KubernetesNodeGroupStatus $s): string => $s->value, $statuses)]],
        );

        return $this->mapCollection(KubernetesNodeGroup::class, $payload);
    }

    /**
     * Returns one node group; a failed one carries the failure cause.
     */
    public function getNodeGroup(string $ownerId, string $kubernetesId, string $nodeGroupId): KubernetesNodeGroup
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet($this->nodeGroupPath($ownerId, $kubernetesId, $nodeGroupId));

        return $this->mapTo(KubernetesNodeGroup::class, $payload);
    }

    /**
     * Creates a node group in the cluster, deployed asynchronously.
     *
     * @param string                     $name   lowercase RFC 1123 subdomain, at most 55 characters
     * @param array<string, string>|null $labels Kubernetes labels applied to the nodes
     * @param list<KubernetesTaint>|null $taints Kubernetes taints applied to the nodes
     */
    public function createNodeGroup(
        string $ownerId,
        string $kubernetesId,
        string $name,
        KubernetesNodeFlavor $flavor,
        int $targetNodeCount,
        ?int $minNodeCount = null,
        ?int $maxNodeCount = null,
        ?bool $autoscalingEnabled = null,
        ?string $description = null,
        ?string $tag = null,
        ?array $labels = null,
        ?array $taints = null,
    ): KubernetesNodeGroup {
        $body = self::withoutNulls([
            'name' => $name,
            'flavor' => $flavor->value,
            'targetNodeCount' => $targetNodeCount,
            'minNodeCount' => $minNodeCount,
            'maxNodeCount' => $maxNodeCount,
            'autoscalingEnabled' => $autoscalingEnabled,
            'description' => $description,
            'tag' => $tag,
            'labels' => null !== $labels ? (object) $labels : null,
            'taints' => null !== $taints ? self::taintsBody($taints) : null,
        ]);

        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost(
            $this->clusterPath($ownerId, $kubernetesId).'/node-groups',
            ['json' => $body],
        );

        return $this->mapTo(KubernetesNodeGroup::class, $payload);
    }

    /**
     * Updates a node group. The API requires `$name` and `$targetNodeCount`
     * on every update; flavor, taints and labels cannot change after
     * creation. A node count change resizes the group asynchronously.
     *
     * @param KubernetesDrainPolicy|null $drainPolicy what a scale-down does with nodes that will not drain
     */
    public function updateNodeGroup(
        string $ownerId,
        string $kubernetesId,
        string $nodeGroupId,
        string $name,
        int $targetNodeCount,
        ?int $minNodeCount = null,
        ?int $maxNodeCount = null,
        ?bool $autoscalingEnabled = null,
        ?string $description = null,
        ?string $tag = null,
        ?KubernetesDrainPolicy $drainPolicy = null,
    ): KubernetesNodeGroup {
        $body = self::withoutNulls([
            'name' => $name,
            'targetNodeCount' => $targetNodeCount,
            'minNodeCount' => $minNodeCount,
            'maxNodeCount' => $maxNodeCount,
            'autoscalingEnabled' => $autoscalingEnabled,
            'description' => $description,
            'tag' => $tag,
            'drainPolicy' => $drainPolicy?->value,
        ]);

        /** @var array<string, mixed> $payload */
        $payload = $this->httpPatch(
            $this->nodeGroupPath($ownerId, $kubernetesId, $nodeGroupId),
            ['json' => $body],
        );

        return $this->mapTo(KubernetesNodeGroup::class, $payload);
    }

    /**
     * Marks the node group for deletion and returns it with the `TERMINATING`
     * status; its nodes are drained and removed asynchronously.
     */
    public function deleteNodeGroup(string $ownerId, string $kubernetesId, string $nodeGroupId): KubernetesNodeGroup
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpDelete($this->nodeGroupPath($ownerId, $kubernetesId, $nodeGroupId));

        return $this->mapTo(KubernetesNodeGroup::class, $payload);
    }

    /**
     * Asks for the deployment of a node group stuck in `DEPLOYING` to be
     * resumed. A `FAILED` node group is retried automatically.
     */
    public function resumeNodeGroup(string $ownerId, string $kubernetesId, string $nodeGroupId): KubernetesNodeGroup
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost($this->nodeGroupPath($ownerId, $kubernetesId, $nodeGroupId).'/resume');

        return $this->mapTo(KubernetesNodeGroup::class, $payload);
    }

    /**
     * Lists the cluster's standalone nodes (nodes outside any node group).
     *
     * @return list<KubernetesNode>
     */
    public function listNodes(string $ownerId, string $kubernetesId): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->clusterPath($ownerId, $kubernetesId).'/nodes');

        return $this->mapCollection(KubernetesNode::class, $payload);
    }

    /**
     * Returns one standalone node.
     */
    public function getNode(string $ownerId, string $kubernetesId, string $nodeId): KubernetesNode
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet($this->nodePath($ownerId, $kubernetesId, $nodeId));

        return $this->mapTo(KubernetesNode::class, $payload);
    }

    /**
     * Creates a standalone node in the cluster, deployed asynchronously.
     *
     * @param string                     $name   lowercase RFC 1123 subdomain, at most 55 characters
     * @param array<string, string>|null $labels Kubernetes labels applied to the node
     * @param list<KubernetesTaint>|null $taints Kubernetes taints applied to the node
     */
    public function createNode(
        string $ownerId,
        string $kubernetesId,
        string $name,
        KubernetesNodeFlavor $flavor,
        ?string $description = null,
        ?string $tag = null,
        ?array $labels = null,
        ?array $taints = null,
    ): KubernetesNode {
        $body = self::withoutNulls([
            'name' => $name,
            'flavor' => $flavor->value,
            'description' => $description,
            'tag' => $tag,
            'labels' => null !== $labels ? (object) $labels : null,
            'taints' => null !== $taints ? self::taintsBody($taints) : null,
        ]);

        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost(
            $this->clusterPath($ownerId, $kubernetesId).'/nodes',
            ['json' => $body],
        );

        return $this->mapTo(KubernetesNode::class, $payload);
    }

    /**
     * Marks the standalone node for deletion and returns it; it is drained
     * and removed asynchronously.
     */
    public function deleteNode(string $ownerId, string $kubernetesId, string $nodeId): KubernetesNode
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpDelete($this->nodePath($ownerId, $kubernetesId, $nodeId));

        return $this->mapTo(KubernetesNode::class, $payload);
    }

    /**
     * Returns the owner's Kubernetes quota: its CPU and RAM ceilings.
     *
     * @return array<int|string, mixed> `{id, tenantId, tags: list<string>, quotas: list<array>}`, each quota
     *                                  tagged by `type` (`MillivCPUMaxLimit` or `RamMaxUsage`)
     */
    public function quota(string $ownerId): array
    {
        return $this->httpGet($this->ownerBase($ownerId).'/quota');
    }

    /**
     * Returns the resources each active control plane or node group of the
     * owner currently uses, as checked against the quota.
     *
     * @return list<array<string, mixed>> entries `{id, clusterId, ownerId, itemType, cpuMillicores, ramBytes, createdAt?, deactivatedAt?}`
     */
    public function usage(string $ownerId): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->ownerBase($ownerId).'/usage');

        return $payload;
    }

    /**
     * Lists the zones where the owner may create a cluster, with the
     * deployment profiles it may use there.
     *
     * @return list<KubernetesOwnerZone>
     */
    public function zones(string $ownerId): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->ownerBase($ownerId).'/zones');

        return $this->mapCollection(KubernetesOwnerZone::class, $payload);
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private static function withoutNulls(array $values): array
    {
        return array_filter($values, static fn (mixed $value): bool => null !== $value);
    }

    /**
     * @return array<string, bool|string>
     */
    private static function featuresBody(KubernetesClusterFeatures $features): array
    {
        /** @var array<string, bool|string> $body */
        $body = self::withoutNulls([
            'autoscalingEnabled' => $features->autoscalingEnabled,
            'csi' => $features->csi,
            'nodeAutoprovisioning' => $features->nodeAutoprovisioning,
            'registries' => $features->registries,
        ]);

        return $body;
    }

    /**
     * @param list<KubernetesTaint> $taints
     *
     * @return list<array<string, string>>
     */
    private static function taintsBody(array $taints): array
    {
        $body = [];
        foreach ($taints as $taint) {
            $entry = ['key' => $taint->key, 'effect' => $taint->effect->value];
            if (null !== $taint->value) {
                $entry['value'] = $taint->value;
            }
            $body[] = $entry;
        }

        return $body;
    }

    private function ownerBase(string $ownerId): string
    {
        return '/kubernetes/organisations/'.rawurlencode($ownerId);
    }

    private function clusterPath(string $ownerId, string $kubernetesId): string
    {
        return $this->ownerBase($ownerId).'/clusters/'.rawurlencode($kubernetesId);
    }

    private function nodeGroupPath(string $ownerId, string $kubernetesId, string $nodeGroupId): string
    {
        return $this->clusterPath($ownerId, $kubernetesId).'/node-groups/'.rawurlencode($nodeGroupId);
    }

    private function nodePath(string $ownerId, string $kubernetesId, string $nodeId): string
    {
        return $this->clusterPath($ownerId, $kubernetesId).'/nodes/'.rawurlencode($nodeId);
    }
}
