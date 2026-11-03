<?php

namespace CleverCloud\Sdk\Resource\V4;

use CleverCloud\Sdk\Model\PulsarCluster;
use CleverCloud\Sdk\Model\PulsarTopic;
use CleverCloud\Sdk\Resource\AbstractV4Resource;

/**
 * Manages the topics of a Pulsar add-on and reads Pulsar clusters, under
 * `/v4/addon-providers/addon-pulsar`. Storage policies live in
 * {@see PulsarPoliciesResource}.
 *
 * Every topic method targets persistent topics (`/topics`) by default; pass
 * `persistent: false` to target non-persistent topics
 * (`/non-persistent-topics`).
 */
final readonly class PulsarResource extends AbstractV4Resource
{
    /**
     * Lists the topics of the add-on's namespace.
     *
     * @return list<PulsarTopic>
     */
    public function topics(string $pulsarId, bool $persistent = true): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->topicsPath($pulsarId, $persistent));

        return $this->mapCollection(PulsarTopic::class, $payload);
    }

    /**
     * Creates a topic in the add-on's namespace.
     */
    public function createTopic(string $pulsarId, string $topic, bool $persistent = true): void
    {
        $this->httpPost($this->topicPath($pulsarId, $topic, $persistent));
    }

    /**
     * Deletes a topic from the add-on's namespace.
     */
    public function deleteTopic(string $pulsarId, string $topic, bool $persistent = true): void
    {
        $this->httpDelete($this->topicPath($pulsarId, $topic, $persistent));
    }

    /**
     * Issues a new access token (Biscuit) restricted to this topic, to hand
     * to a producer or consumer that must reach this topic only.
     */
    public function createTopicToken(string $pulsarId, string $topic, bool $persistent = true): string
    {
        $response = $this->httpStream(
            'POST',
            $this->topicPath($pulsarId, $topic, $persistent).'/token',
            ['headers' => ['Accept' => 'text/plain']],
        );

        return (string) $response->getBody();
    }

    /**
     * Unloads the topic from the broker serving it.
     */
    public function unloadTopic(string $pulsarId, string $topic, bool $persistent = true): void
    {
        $this->httpPut($this->topicPath($pulsarId, $topic, $persistent).'/unload');
    }

    /**
     * Returns a Pulsar cluster: endpoints, ports, version, zone and the plans
     * it supports.
     */
    public function cluster(string $clusterId): PulsarCluster
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet('/addon-providers/addon-pulsar/clusters/'.rawurlencode($clusterId));

        return $this->mapTo(PulsarCluster::class, $payload);
    }

    private function topicsPath(string $pulsarId, bool $persistent): string
    {
        return '/addon-providers/addon-pulsar/addons/'.rawurlencode($pulsarId)
            .($persistent ? '/topics' : '/non-persistent-topics');
    }

    private function topicPath(string $pulsarId, string $topic, bool $persistent): string
    {
        return $this->topicsPath($pulsarId, $persistent).'/'.rawurlencode($topic);
    }
}
