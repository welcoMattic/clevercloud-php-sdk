<?php

namespace CleverCloud\Sdk\Model;

use AutoMapper\Attribute\MapFrom;

/**
 * A Pulsar cluster: its endpoints and ports, version, zone, availability and
 * the plans it supports. Returned by
 * `/v4/addon-providers/addon-pulsar/clusters/{clusterId}`.
 */
final readonly class PulsarCluster
{
    /**
     * @param list<string> $supportedPlans
     */
    public function __construct(
        public string $id,
        public ?string $url = null,
        public ?string $version = null,
        public ?string $zone = null,
        public ?bool $available = null,
        #[MapFrom(property: 'pulsar_port')]
        public ?int $pulsarPort = null,
        #[MapFrom(property: 'pulsar_tls_port')]
        public ?int $pulsarTlsPort = null,
        #[MapFrom(property: 'web_port')]
        public ?int $webPort = null,
        #[MapFrom(property: 'web_tls_port')]
        public ?int $webTlsPort = null,
        #[MapFrom(property: 'admin_host')]
        public ?string $adminHost = null,
        #[MapFrom(property: 'support_cold_storage')]
        public ?bool $supportColdStorage = null,
        #[MapFrom(property: 'supported_plans')]
        public array $supportedPlans = [],
    ) {
    }
}
