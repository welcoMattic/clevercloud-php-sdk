<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V4;

use CleverCloud\Sdk\Exception\JsonException;
use CleverCloud\Sdk\Exception\NotFoundException;
use CleverCloud\Sdk\Model\Enum\KubernetesClusterStatus;
use CleverCloud\Sdk\Model\Enum\KubernetesDeploymentEventOperation;
use CleverCloud\Sdk\Model\Enum\KubernetesDeploymentEventStatus;
use CleverCloud\Sdk\Model\Enum\KubernetesDrainPolicy;
use CleverCloud\Sdk\Model\Enum\KubernetesKubeconfigType;
use CleverCloud\Sdk\Model\Enum\KubernetesNodeFlavor;
use CleverCloud\Sdk\Model\Enum\KubernetesNodeGroupStatus;
use CleverCloud\Sdk\Model\Enum\KubernetesNodeStatus;
use CleverCloud\Sdk\Model\Enum\KubernetesOperationScope;
use CleverCloud\Sdk\Model\Enum\KubernetesTaintEffect;
use CleverCloud\Sdk\Model\KubernetesClusterFeatures;
use CleverCloud\Sdk\Model\KubernetesTaint;
use CleverCloud\Sdk\Resource\V4\KubernetesResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(KubernetesResource::class)]
final class KubernetesResourceTest extends TestCase
{
    private const string BASE = 'https://api.clever-cloud.com/v4/kubernetes/organisations/orga_1';
    private const string CLUSTER = self::BASE.'/clusters/kubernetes_1';

    public function testServiceConfigHitsProductEndpoint(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'topologies' => [['topology' => 'ALL_IN_ONE', 'availableFlavors' => ['M'], 'replicationFactor' => ['min' => 1, 'max' => 5]]],
            'versions' => ['available' => ['1.34', '1.35'], 'default' => '1.35'],
        ]);

        $config = $this->resource($response)->serviceConfig();

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v4/kubernetes-product', $response->getRequestUrl());
        self::assertSame(['available' => ['1.34', '1.35'], 'default' => '1.35'], $config['versions']);
    }

    public function testPluginCatalogHitsCatalogEndpoint(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['bases' => [], 'migrations' => []]);

        $catalog = $this->resource($response)->pluginCatalog();

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v4/kubernetes/plugins/catalog', $response->getRequestUrl());
        self::assertSame(['bases' => [], 'migrations' => []], $catalog);
    }

    public function testListClustersWithoutStatusSendsNoQuery(): void
    {
        $response = ResourceFactory::jsonResponse(200, [$this->clusterPayload()]);

        $clusters = $this->resource($response)->listClusters('orga_1');

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(self::BASE.'/clusters', $response->getRequestUrl());
        self::assertCount(1, $clusters);
        self::assertSame('kubernetes_1', $clusters[0]->id);
    }

    public function testListClustersRepeatsStatusQuery(): void
    {
        $response = ResourceFactory::jsonResponse(200, []);

        $this->resource($response)->listClusters(
            'orga_1',
            [KubernetesClusterStatus::Active, KubernetesClusterStatus::Failed],
        );

        self::assertSame(self::BASE.'/clusters?status=ACTIVE&status=FAILED', $response->getRequestUrl());
    }

    public function testGetClusterMapsTheFullDetailResponse(): void
    {
        $response = ResourceFactory::jsonResponse(200, $this->clusterPayload(detail: true));

        $cluster = $this->resource($response)->getCluster('orga_1', 'kubernetes_1');

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(self::CLUSTER, $response->getRequestUrl());

        self::assertSame('kubernetes_1', $cluster->id);
        self::assertSame('orga_1', $cluster->tenantId);
        self::assertSame('prod', $cluster->name);
        self::assertSame(KubernetesClusterStatus::Active, $cluster->status);
        self::assertSame('2026-10-01T10:00:00Z', $cluster->creationDate);
        self::assertSame('1.35', $cluster->version);
        self::assertSame(['env:prod'], $cluster->tags);
        self::assertSame(['topology' => 'ALL_IN_ONE', 'flavor' => 'M', 'replicationFactor' => 1], $cluster->topologyConfig);
        self::assertNull($cluster->description);
        self::assertSame('https://kubernetes-1.k8s.example.com', $cluster->apiServerUrl);
        self::assertSame(123456, $cluster->storageUsageBytes);
        self::assertNull($cluster->failure);
        self::assertNull($cluster->waiting);

        self::assertNotNull($cluster->features);
        self::assertTrue($cluster->features->csi);
        self::assertFalse($cluster->features->autoscalingEnabled);
        self::assertNull($cluster->features->registries);

        self::assertCount(1, $cluster->loadBalancers);
        self::assertSame(['10.0.0.1', '10.0.0.2'], $cluster->loadBalancers[0]->ips);
        self::assertSame('lb.example.com', $cluster->loadBalancers[0]->domainName);

        self::assertCount(1, $cluster->nodeGroups);
        $group = $cluster->nodeGroups[0];
        self::assertSame('workers', $group->name);
        self::assertSame(KubernetesNodeGroupStatus::Ready, $group->status);
        self::assertSame(KubernetesNodeFlavor::TwoXS, $group->flavor);
        self::assertSame(2, $group->currentNodeCount);
        self::assertNull($group->failure);
        self::assertCount(1, $group->nodes);
        self::assertSame(KubernetesNodeStatus::Ready, $group->nodes[0]->status);
        self::assertSame(2, $group->nodes[0]->vCpus);
        self::assertSame(4294967296, $group->nodes[0]->ramBytes);

        self::assertCount(1, $cluster->standaloneNodeGroups);
        self::assertSame([], $cluster->standaloneNodeGroups[0]->nodes);
    }

    public function testGetClusterMapsFailureAndWaiting(): void
    {
        $payload = $this->clusterPayload();
        $payload['status'] = 'FAILED';
        $payload['failure'] = ['operation' => 'DEPLOY', 'message' => 'boom', 'occurredAt' => '2026-10-02T10:00:00Z', 'step' => null];
        $payload['waiting'] = ['dependency' => 'network-group', 'since' => '2026-10-02T09:00:00Z', 'attempts' => 3];
        $response = ResourceFactory::jsonResponse(200, $payload);

        $cluster = $this->resource($response)->getCluster('orga_1', 'kubernetes_1');

        self::assertSame(KubernetesClusterStatus::Failed, $cluster->status);
        self::assertNotNull($cluster->failure);
        self::assertSame('DEPLOY', $cluster->failure->operation);
        self::assertSame('boom', $cluster->failure->message);
        self::assertNull($cluster->failure->step);
        self::assertSame('network-group', $cluster->waiting['dependency'] ?? null);
    }

    public function testCreateClusterPostsOnlyGivenFields(): void
    {
        $response = ResourceFactory::jsonResponse(201, $this->clusterPayload());

        $cluster = $this->resource($response)->createCluster(
            'orga_1',
            'prod',
            version: '1.35',
            zoneId: 'zone-uuid',
            tags: ['env:prod'],
            features: new KubernetesClusterFeatures(csi: true),
            topologyConfig: ['topology' => 'ALL_IN_ONE', 'flavor' => 'M', 'replicationFactor' => 1],
            nodeGroups: [['name' => 'workers', 'flavor' => 'M', 'targetNodeCount' => 2]],
        );

        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(self::BASE.'/clusters', $response->getRequestUrl());
        self::assertSame(
            '{"name":"prod","version":"1.35","zoneId":"zone-uuid","tags":["env:prod"],"features":{"csi":true},'
            .'"topologyConfig":{"topology":"ALL_IN_ONE","flavor":"M","replicationFactor":1},'
            .'"nodeGroups":[{"name":"workers","flavor":"M","targetNodeCount":2}]}',
            $response->getRequestOptions()['body'],
        );
        self::assertSame('kubernetes_1', $cluster->id);
    }

    public function testCreateClusterWithNameOnly(): void
    {
        $response = ResourceFactory::jsonResponse(201, $this->clusterPayload());

        $this->resource($response)->createCluster('orga_1', 'prod');

        self::assertSame('{"name":"prod"}', $response->getRequestOptions()['body']);
    }

    public function testUpdateClusterPatchesGivenFields(): void
    {
        $response = ResourceFactory::jsonResponse(200, $this->clusterPayload());

        $this->resource($response)->updateCluster(
            'orga_1',
            'kubernetes_1',
            description: 'Production',
            features: new KubernetesClusterFeatures(autoscalingEnabled: true, nodeAutoprovisioning: false),
        );

        self::assertSame('PATCH', $response->getRequestMethod());
        self::assertSame(self::CLUSTER, $response->getRequestUrl());
        self::assertSame(
            '{"description":"Production","features":{"autoscalingEnabled":true,"nodeAutoprovisioning":false}}',
            $response->getRequestOptions()['body'],
        );
    }

    public function testUpdateClusterSendsEmptyFeaturesAsObject(): void
    {
        $response = ResourceFactory::jsonResponse(200, $this->clusterPayload());

        $this->resource($response)->updateCluster('orga_1', 'kubernetes_1', features: new KubernetesClusterFeatures());

        self::assertSame('{"features":{}}', $response->getRequestOptions()['body']);
    }

    public function testUpdateClusterWithoutFieldsSendsEmptyObject(): void
    {
        $response = ResourceFactory::jsonResponse(200, $this->clusterPayload());

        $this->resource($response)->updateCluster('orga_1', 'kubernetes_1');

        self::assertSame('{}', $response->getRequestOptions()['body']);
    }

    public function testDeleteClusterReturnsTheDeletingCluster(): void
    {
        $payload = $this->clusterPayload();
        $payload['status'] = 'DELETING';
        $response = ResourceFactory::jsonResponse(200, $payload);

        $cluster = $this->resource($response)->deleteCluster('orga_1', 'kubernetes_1');

        self::assertSame('DELETE', $response->getRequestMethod());
        self::assertSame(self::CLUSTER, $response->getRequestUrl());
        self::assertSame(KubernetesClusterStatus::Deleting, $cluster->status);
    }

    public function testGetClusterThrowsNotFound(): void
    {
        $response = ResourceFactory::jsonResponse(404, ['code' => 'not-found', 'message' => 'No such cluster']);

        $this->expectException(NotFoundException::class);

        $this->resource($response)->getCluster('orga_1', 'kubernetes_404');
    }

    public function testEnableCephCsiPostsWithoutBody(): void
    {
        $response = ResourceFactory::jsonResponse(200, $this->clusterPayload());

        $this->resource($response)->enableCephCsi('orga_1', 'kubernetes_1');

        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(self::CLUSTER.'/csi/ceph', $response->getRequestUrl());
    }

    public function testRedeployWithoutOptionsSendsNoBody(): void
    {
        $response = ResourceFactory::jsonResponse(200, $this->clusterPayload());

        $this->resource($response)->redeploy('orga_1', 'kubernetes_1');

        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(self::CLUSTER.'/redeploy', $response->getRequestUrl());
        self::assertSame('', $response->getRequestOptions()['body'] ?? '');
    }

    public function testRedeployWithOptionsPostsJson(): void
    {
        $response = ResourceFactory::jsonResponse(200, $this->clusterPayload());

        $this->resource($response)->redeploy(
            'orga_1',
            'kubernetes_1',
            beforeYamls: ['YmVmb3Jl'],
            drainPolicy: KubernetesDrainPolicy::Force,
        );

        self::assertSame('{"beforeYamls":["YmVmb3Jl"],"drainPolicy":"FORCE"}', $response->getRequestOptions()['body']);
    }

    public function testResumeWithoutDrainPolicySendsNoBody(): void
    {
        $response = ResourceFactory::jsonResponse(200, $this->clusterPayload());

        $this->resource($response)->resume('orga_1', 'kubernetes_1');

        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(self::CLUSTER.'/resume', $response->getRequestUrl());
        self::assertSame('', $response->getRequestOptions()['body'] ?? '');
    }

    public function testResumeWithDrainPolicyPostsJson(): void
    {
        $response = ResourceFactory::jsonResponse(200, $this->clusterPayload());

        $this->resource($response)->resume('orga_1', 'kubernetes_1', KubernetesDrainPolicy::Strict);

        self::assertSame('{"drainPolicy":"STRICT"}', $response->getRequestOptions()['body']);
    }

    public function testReconcilePluginsPosts(): void
    {
        $payload = $this->clusterPayload();
        $payload['status'] = 'RECONCILING';
        $response = ResourceFactory::jsonResponse(200, $payload);

        $cluster = $this->resource($response)->reconcilePlugins('orga_1', 'kubernetes_1');

        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(self::CLUSTER.'/plugins/reconcile', $response->getRequestUrl());
        self::assertSame(KubernetesClusterStatus::Reconciling, $cluster->status);
    }

    public function testMigrateSniPosts(): void
    {
        $response = ResourceFactory::jsonResponse(200, $this->clusterPayload());

        $this->resource($response)->migrateSni('orga_1', 'kubernetes_1');

        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(self::CLUSTER.'/sni-migration', $response->getRequestUrl());
    }

    public function testCheckVersionMapsResponse(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'available' => ['1.35'],
            'installed' => '1.34',
            'latest' => '1.35',
            'needUpdate' => true,
            'workerVersions' => ['v1.34.1' => 3],
            'workersInSync' => true,
        ]);

        $check = $this->resource($response)->checkVersion('orga_1', 'kubernetes_1', workers: true);

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(self::CLUSTER.'/version/check?workers=true', $response->getRequestUrl());
        self::assertSame('1.34', $check->installed);
        self::assertSame('1.35', $check->latest);
        self::assertTrue($check->needUpdate);
        self::assertSame(['1.35'], $check->available);
        self::assertSame(['v1.34.1' => 3], $check->workerVersions);
        self::assertTrue($check->workersInSync);
    }

    public function testCheckVersionWithoutWorkersSendsNoQuery(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'available' => [],
            'installed' => '1.35',
            'latest' => '1.35',
            'needUpdate' => false,
        ]);

        $check = $this->resource($response)->checkVersion('orga_1', 'kubernetes_1');

        self::assertSame(self::CLUSTER.'/version/check', $response->getRequestUrl());
        self::assertNull($check->workerVersions);
        self::assertNull($check->workersInSync);
    }

    public function testUpdateVersionPostsJson(): void
    {
        $response = ResourceFactory::jsonResponse(200, $this->clusterPayload());

        $this->resource($response)->updateVersion(
            'orga_1',
            'kubernetes_1',
            '1.35',
            KubernetesOperationScope::ControlPlane,
            KubernetesDrainPolicy::Force,
        );

        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(self::CLUSTER.'/version/update', $response->getRequestUrl());
        self::assertSame(
            '{"targetVersion":"1.35","scope":"CONTROL_PLANE","drainPolicy":"FORCE"}',
            $response->getRequestOptions()['body'],
        );
    }

    public function testKubeconfigReturnsRawYaml(): void
    {
        $yaml = "apiVersion: v1\nkind: Config\nclusters: []\n";
        $response = new MockResponse($yaml, [
            'http_code' => 200,
            'response_headers' => ['content-type' => 'text/plain'],
        ]);

        $kubeconfig = $this->resource($response)->kubeconfig(
            'orga_1',
            'kubernetes_1',
            KubernetesKubeconfigType::NetworkGroup,
            'presigned-token',
        );

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(
            self::CLUSTER.'/kubeconfig.yaml?type=NETWORKGROUP&authorization=presigned-token',
            $response->getRequestUrl(),
        );
        self::assertSame('Accept: text/plain', ResourceFactory::findHeader($response, 'Accept'));
        self::assertSame($yaml, $kubeconfig);
    }

    public function testKubeconfigWithoutOptionsSendsNoQuery(): void
    {
        $response = new MockResponse('apiVersion: v1', ['http_code' => 200]);

        $this->resource($response)->kubeconfig('orga_1', 'kubernetes_1');

        self::assertSame(self::CLUSTER.'/kubeconfig.yaml', $response->getRequestUrl());
    }

    public function testKubeconfigPresignedUrlReturnsUrl(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['url' => 'https://api.clever-cloud.com/v4/kubeconfig.yaml?authorization=t']);

        $url = $this->resource($response)->kubeconfigPresignedUrl('orga_1', 'kubernetes_1');

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(self::CLUSTER.'/kubeconfig/presigned-url', $response->getRequestUrl());
        self::assertSame('https://api.clever-cloud.com/v4/kubeconfig.yaml?authorization=t', $url);
    }

    public function testKubeconfigPresignedUrlThrowsWithoutUrl(): void
    {
        $response = ResourceFactory::jsonResponse(200, []);

        $this->expectException(JsonException::class);

        $this->resource($response)->kubeconfigPresignedUrl('orga_1', 'kubernetes_1');
    }

    public function testEventsReturnsRawEntries(): void
    {
        $event = ['event' => 'CLUSTER_STATUS', 'date' => '2026-10-01T10:00:00Z', 'status' => 'ACTIVE', 'failure' => null];
        $response = ResourceFactory::jsonResponse(200, [$event]);

        $events = $this->resource($response)->events('orga_1', 'kubernetes_1', limit: 10);

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(self::CLUSTER.'/events?limit=10', $response->getRequestUrl());
        self::assertSame([$event], $events);
    }

    public function testDeploymentEventsMapsTypedEvents(): void
    {
        $response = ResourceFactory::jsonResponse(200, [[
            'operation' => 'NODEGROUP_SCALE',
            'status' => 'COMPLETED',
            'createdAt' => '2026-10-01T10:00:00Z',
            'stepName' => 'RESIZE',
            'nodeGroupId' => 'nodegroup_1',
            'detail' => null,
        ]]);

        $events = $this->resource($response)->deploymentEvents('orga_1', 'kubernetes_1');

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(self::CLUSTER.'/deployment-events', $response->getRequestUrl());
        self::assertCount(1, $events);
        self::assertSame(KubernetesDeploymentEventOperation::NodeGroupScale, $events[0]->operation);
        self::assertSame(KubernetesDeploymentEventStatus::Completed, $events[0]->status);
        self::assertSame('RESIZE', $events[0]->stepName);
        self::assertSame('nodegroup_1', $events[0]->nodeGroupId);
        self::assertNull($events[0]->detail);
    }

    public function testListNodeGroupsRepeatsStatusQuery(): void
    {
        $response = ResourceFactory::jsonResponse(200, [$this->nodeGroupPayload()]);

        $groups = $this->resource($response)->listNodeGroups(
            'orga_1',
            'kubernetes_1',
            [KubernetesNodeGroupStatus::Ready, KubernetesNodeGroupStatus::Failed],
        );

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(self::CLUSTER.'/node-groups?status=READY&status=FAILED', $response->getRequestUrl());
        self::assertCount(1, $groups);
        self::assertSame('nodegroup_1', $groups[0]->id);
    }

    public function testGetNodeGroupMapsTheFullResponse(): void
    {
        $payload = $this->nodeGroupPayload();
        $payload['status'] = 'FAILED';
        $payload['failure'] = [
            'operation' => 'NODEGROUP_SCALE',
            'message' => 'drain failed',
            'occurredAt' => '2026-10-02T10:00:00Z',
            'step' => 'DRAIN',
            'undrainedNodes' => [['outcome' => 'BLOCKED', 'pods' => ['default/web-0'], 'reason' => 'PDB', 'nodeName' => 'workers-1']],
        ];
        $response = ResourceFactory::jsonResponse(200, $payload);

        $group = $this->resource($response)->getNodeGroup('orga_1', 'kubernetes_1', 'nodegroup_1');

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(self::CLUSTER.'/node-groups/nodegroup_1', $response->getRequestUrl());
        self::assertSame('nodegroup_1', $group->id);
        self::assertSame('kubernetes_1', $group->clusterId);
        self::assertSame('workers', $group->name);
        self::assertSame(KubernetesNodeFlavor::M, $group->flavor);
        self::assertSame(KubernetesNodeGroupStatus::Failed, $group->status);
        self::assertSame(2, $group->currentNodeCount);
        self::assertSame(3, $group->targetNodeCount);
        self::assertSame(1, $group->minNodeCount);
        self::assertSame(5, $group->maxNodeCount);
        self::assertTrue($group->autoscalingEnabled);
        self::assertSame('2026-10-01T10:00:00Z', $group->createdAt);
        self::assertNull($group->updatedAt);
        self::assertNull($group->description);
        self::assertSame(['tier' => 'web'], $group->labels);
        self::assertCount(1, $group->taints);
        self::assertSame('dedicated', $group->taints[0]->key);
        self::assertSame('web', $group->taints[0]->value);
        self::assertSame(KubernetesTaintEffect::NoSchedule, $group->taints[0]->effect);
        self::assertNotNull($group->failure);
        self::assertSame('DRAIN', $group->failure->step);
        self::assertCount(1, $group->failure->undrainedNodes);
        self::assertSame('BLOCKED', $group->failure->undrainedNodes[0]['outcome'] ?? null);
    }

    public function testCreateNodeGroupPostsJson(): void
    {
        $response = ResourceFactory::jsonResponse(201, $this->nodeGroupPayload());

        $group = $this->resource($response)->createNodeGroup(
            'orga_1',
            'kubernetes_1',
            'workers',
            KubernetesNodeFlavor::M,
            3,
            minNodeCount: 1,
            maxNodeCount: 5,
            autoscalingEnabled: true,
            labels: ['tier' => 'web'],
            taints: [
                new KubernetesTaint('dedicated', KubernetesTaintEffect::NoSchedule, 'web'),
                new KubernetesTaint('spot', KubernetesTaintEffect::PreferNoSchedule),
            ],
        );

        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(self::CLUSTER.'/node-groups', $response->getRequestUrl());
        self::assertSame(
            '{"name":"workers","flavor":"M","targetNodeCount":3,"minNodeCount":1,"maxNodeCount":5,'
            .'"autoscalingEnabled":true,"labels":{"tier":"web"},'
            .'"taints":[{"key":"dedicated","effect":"NoSchedule","value":"web"},{"key":"spot","effect":"PreferNoSchedule"}]}',
            $response->getRequestOptions()['body'],
        );
        self::assertSame('nodegroup_1', $group->id);
    }

    public function testCreateNodeGroupSendsEmptyLabelsAsObject(): void
    {
        $response = ResourceFactory::jsonResponse(201, $this->nodeGroupPayload());

        $this->resource($response)->createNodeGroup('orga_1', 'kubernetes_1', 'workers', KubernetesNodeFlavor::TwoXS, 1, labels: []);

        self::assertSame(
            '{"name":"workers","flavor":"2XS","targetNodeCount":1,"labels":{}}',
            $response->getRequestOptions()['body'],
        );
    }

    public function testUpdateNodeGroupPatchesJson(): void
    {
        $response = ResourceFactory::jsonResponse(200, $this->nodeGroupPayload());

        $this->resource($response)->updateNodeGroup(
            'orga_1',
            'kubernetes_1',
            'nodegroup_1',
            'workers',
            1,
            drainPolicy: KubernetesDrainPolicy::Force,
        );

        self::assertSame('PATCH', $response->getRequestMethod());
        self::assertSame(self::CLUSTER.'/node-groups/nodegroup_1', $response->getRequestUrl());
        self::assertSame(
            '{"name":"workers","targetNodeCount":1,"drainPolicy":"FORCE"}',
            $response->getRequestOptions()['body'],
        );
    }

    public function testDeleteNodeGroupReturnsTheTerminatingGroup(): void
    {
        $payload = $this->nodeGroupPayload();
        $payload['status'] = 'TERMINATING';
        $response = ResourceFactory::jsonResponse(200, $payload);

        $group = $this->resource($response)->deleteNodeGroup('orga_1', 'kubernetes_1', 'nodegroup_1');

        self::assertSame('DELETE', $response->getRequestMethod());
        self::assertSame(self::CLUSTER.'/node-groups/nodegroup_1', $response->getRequestUrl());
        self::assertSame(KubernetesNodeGroupStatus::Terminating, $group->status);
    }

    public function testResumeNodeGroupPosts(): void
    {
        $payload = $this->nodeGroupPayload();
        $payload['status'] = 'DEPLOYING';
        $response = ResourceFactory::jsonResponse(200, $payload);

        $group = $this->resource($response)->resumeNodeGroup('orga_1', 'kubernetes_1', 'nodegroup_1');

        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(self::CLUSTER.'/node-groups/nodegroup_1/resume', $response->getRequestUrl());
        self::assertSame(KubernetesNodeGroupStatus::Deploying, $group->status);
    }

    public function testListNodesMapsStandaloneNodes(): void
    {
        $response = ResourceFactory::jsonResponse(200, [$this->nodePayload()]);

        $nodes = $this->resource($response)->listNodes('orga_1', 'kubernetes_1');

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(self::CLUSTER.'/nodes', $response->getRequestUrl());
        self::assertCount(1, $nodes);
        self::assertSame('node_1', $nodes[0]->id);
        self::assertSame(KubernetesNodeFlavor::L, $nodes[0]->flavor);
        self::assertSame(KubernetesNodeStatus::Ready, $nodes[0]->status);
        self::assertSame(['gpu' => 'true'], $nodes[0]->labels);
        self::assertSame(KubernetesTaintEffect::NoExecute, $nodes[0]->taints[0]->effect);
        self::assertNull($nodes[0]->taints[0]->value);
    }

    public function testGetNodeHitsNodePath(): void
    {
        $response = ResourceFactory::jsonResponse(200, $this->nodePayload());

        $node = $this->resource($response)->getNode('orga_1', 'kubernetes_1', 'node_1');

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(self::CLUSTER.'/nodes/node_1', $response->getRequestUrl());
        self::assertSame('kubernetes_1', $node->clusterId);
        self::assertSame('gpu-1', $node->name);
    }

    public function testCreateNodePostsJson(): void
    {
        $response = ResourceFactory::jsonResponse(201, $this->nodePayload());

        $this->resource($response)->createNode(
            'orga_1',
            'kubernetes_1',
            'gpu-1',
            KubernetesNodeFlavor::L,
            tag: 'gpu',
            taints: [new KubernetesTaint('gpu', KubernetesTaintEffect::NoExecute)],
        );

        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(self::CLUSTER.'/nodes', $response->getRequestUrl());
        self::assertSame(
            '{"name":"gpu-1","flavor":"L","tag":"gpu","taints":[{"key":"gpu","effect":"NoExecute"}]}',
            $response->getRequestOptions()['body'],
        );
    }

    public function testDeleteNodeHitsDelete(): void
    {
        $response = ResourceFactory::jsonResponse(200, $this->nodePayload());

        $node = $this->resource($response)->deleteNode('orga_1', 'kubernetes_1', 'node_1');

        self::assertSame('DELETE', $response->getRequestMethod());
        self::assertSame(self::CLUSTER.'/nodes/node_1', $response->getRequestUrl());
        self::assertSame('node_1', $node->id);
    }

    public function testQuotaReturnsRawPayload(): void
    {
        $quota = [
            'id' => 'quota_1',
            'tenantId' => 'orga_1',
            'tags' => [],
            'quotas' => [['type' => 'MillivCPUMaxLimit', 'maximum' => 16000]],
        ];
        $response = ResourceFactory::jsonResponse(200, $quota);

        self::assertSame($quota, $this->resource($response)->quota('orga_1'));
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(self::BASE.'/quota', $response->getRequestUrl());
    }

    public function testUsageReturnsRawEntries(): void
    {
        $entry = ['id' => 'item_1', 'clusterId' => 'kubernetes_1', 'ownerId' => 'orga_1', 'itemType' => 'NODE_GROUP', 'cpuMillicores' => 4000, 'ramBytes' => 8589934592];
        $response = ResourceFactory::jsonResponse(200, [$entry]);

        self::assertSame([$entry], $this->resource($response)->usage('orga_1'));
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(self::BASE.'/usage', $response->getRequestUrl());
    }

    public function testZonesMapsOwnerZones(): void
    {
        $response = ResourceFactory::jsonResponse(200, [[
            'id' => '5f1d3e4c-0000-0000-0000-000000000000',
            'name' => 'par',
            'deploymentProfiles' => [['id' => 'kubernetes_deployment_profile_1', 'prodDefault' => true]],
        ]]);

        $zones = $this->resource($response)->zones('orga_1');

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(self::BASE.'/zones', $response->getRequestUrl());
        self::assertCount(1, $zones);
        self::assertSame('par', $zones[0]->name);
        self::assertSame([['id' => 'kubernetes_deployment_profile_1', 'prodDefault' => true]], $zones[0]->deploymentProfiles);
    }

    public function testPathSegmentsAreEncoded(): void
    {
        $response = ResourceFactory::jsonResponse(200, $this->nodePayload());

        $this->resource($response)->getNode('orga/1', 'kubernetes 1', 'node?1');

        self::assertSame(
            'https://api.clever-cloud.com/v4/kubernetes/organisations/orga%2F1/clusters/kubernetes%201/nodes/node%3F1',
            $response->getRequestUrl(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function clusterPayload(bool $detail = false): array
    {
        $payload = [
            'id' => 'kubernetes_1',
            'tenantId' => 'orga_1',
            'name' => 'prod',
            'tags' => ['env:prod'],
            'status' => 'ACTIVE',
            'creationDate' => '2026-10-01T10:00:00Z',
            'version' => '1.35',
            'topologyConfig' => ['topology' => 'ALL_IN_ONE', 'flavor' => 'M', 'replicationFactor' => 1],
            'description' => null,
            'deploymentProfileId' => 'kubernetes_deployment_profile_1',
            'zoneId' => '5f1d3e4c-0000-0000-0000-000000000000',
            'locationId' => '5f1d3e4c-0000-0000-0000-000000000000',
            'storageUsageBytes' => 123456,
            'features' => ['autoscalingEnabled' => false, 'csi' => true, 'nodeAutoprovisioning' => false, 'registries' => null],
            'failure' => null,
            'waiting' => null,
            'loadBalancers' => [],
            'nodeGroups' => [],
            'standaloneNodeGroups' => [],
        ];

        if ($detail) {
            $payload['apiServerUrl'] = 'https://kubernetes-1.k8s.example.com';
            $payload['loadBalancers'] = [
                ['id' => 'lb_1', 'flavor' => 'S', 'ips' => ['10.0.0.1', '10.0.0.2'], 'domainName' => 'lb.example.com'],
            ];
            $payload['nodeGroups'] = [[
                'id' => 'nodegroup_1',
                'name' => 'workers',
                'status' => 'READY',
                'flavor' => '2XS',
                'currentNodeCount' => 2,
                'targetNodeCount' => 2,
                'failure' => null,
                'nodes' => [['id' => 'node_a', 'name' => 'workers-a', 'status' => 'READY', 'vCpus' => 2, 'ramBytes' => 4294967296]],
            ]];
            $payload['standaloneNodeGroups'] = [[
                'id' => 'nodegroup_2',
                'name' => 'gpu-1',
                'status' => 'READY',
                'flavor' => 'L',
                'currentNodeCount' => 1,
                'targetNodeCount' => 1,
            ]];
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function nodeGroupPayload(): array
    {
        return [
            'id' => 'nodegroup_1',
            'clusterId' => 'kubernetes_1',
            'name' => 'workers',
            'flavor' => 'M',
            'currentNodeCount' => 2,
            'targetNodeCount' => 3,
            'minNodeCount' => 1,
            'maxNodeCount' => 5,
            'taints' => [['key' => 'dedicated', 'effect' => 'NoSchedule', 'value' => 'web']],
            'labels' => ['tier' => 'web'],
            'createdAt' => '2026-10-01T10:00:00Z',
            'updatedAt' => null,
            'status' => 'READY',
            'autoscalingEnabled' => true,
            'description' => null,
            'tag' => null,
            'failure' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function nodePayload(): array
    {
        return [
            'id' => 'node_1',
            'clusterId' => 'kubernetes_1',
            'name' => 'gpu-1',
            'flavor' => 'L',
            'taints' => [['key' => 'gpu', 'effect' => 'NoExecute', 'value' => null]],
            'labels' => ['gpu' => 'true'],
            'status' => 'READY',
            'description' => null,
            'tag' => 'gpu',
        ];
    }

    private function resource(MockResponse $response): KubernetesResource
    {
        return new KubernetesResource(
            ResourceFactory::http(new MockHttpClient([$response])),
            ResourceFactory::mapper(),
        );
    }
}
