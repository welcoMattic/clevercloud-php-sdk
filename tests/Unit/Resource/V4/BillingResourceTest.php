<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V4;

use CleverCloud\Sdk\Resource\V4\BillingResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\RecordingClient;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BillingResource::class)]
final class BillingResourceTest extends TestCase
{
    public function testListInvoicesHitsSelfPath(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(200, [
            [
                'invoiceNumber' => 'INV-001',
                'status' => 'PAID',
                'currency' => 'EUR',
                'totalTaxIncluded' => '120.00',
                'emissionDate' => 1_700_000_000_000,
            ],
            [
                'invoiceNumber' => 'INV-002',
                'status' => 'PENDING',
            ],
        ]));

        $invoices = $this->resource($psr18)->listInvoices();

        self::assertCount(2, $invoices);
        self::assertSame('INV-001', $invoices[0]->invoiceNumber);
        self::assertSame('PAID', $invoices[0]->status);
        self::assertSame('120.00', $invoices[0]->totalTaxIncluded);
        self::assertSame('PENDING', $invoices[1]->status);
        self::assertNotNull($psr18->lastRequest);
        self::assertSame('https://api.clever-cloud.com/v4/billing/self/invoices', (string) $psr18->lastRequest->getUri());
    }

    public function testListInvoicesForOrganisationRoutesUnderOrg(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(200, []));

        $this->resource($psr18)->listInvoices('orga_xyz');

        self::assertNotNull($psr18->lastRequest);
        self::assertSame(
            'https://api.clever-cloud.com/v4/billing/organisations/orga_xyz/invoices',
            (string) $psr18->lastRequest->getUri(),
        );
    }

    public function testGetBalanceHitsCreditsBalance(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(200, [
            'currency' => 'EUR',
            'balance' => 42.5,
            'remainingPrepaid' => 12.75,
        ]));

        $balance = $this->resource($psr18)->getBalance('orga_1');

        self::assertSame(['currency' => 'EUR', 'balance' => 42.5, 'remainingPrepaid' => 12.75], $balance);
        self::assertNotNull($psr18->lastRequest);
        self::assertSame(
            'https://api.clever-cloud.com/v4/billing/organisations/orga_1/credits/balance',
            (string) $psr18->lastRequest->getUri(),
        );
    }

    public function testRemovePaymentMethodHitsDelete(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(204, []));

        $this->resource($psr18)->removePaymentMethod('pm_xyz');

        self::assertNotNull($psr18->lastRequest);
        self::assertSame('DELETE', $psr18->lastRequest->getMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v4/billing/self/payments/methods/pm_xyz',
            (string) $psr18->lastRequest->getUri(),
        );
    }

    public function testConsumptionsAppendsFromToQuery(): void
    {
        $psr18 = new RecordingClient(ResourceFactory::jsonResponse(200, []));

        $this->resource($psr18)->consumptions('orga_1', from: 1_700_000_000_000, to: 1_710_000_000_000);

        self::assertNotNull($psr18->lastRequest);
        self::assertSame(
            'https://api.clever-cloud.com/v4/billing/organisations/orga_1/consumptions?from=1700000000000&to=1710000000000',
            (string) $psr18->lastRequest->getUri(),
        );
    }

    private function resource(RecordingClient $psr18): BillingResource
    {
        return new BillingResource(ResourceFactory::http($psr18), ResourceFactory::mapper());
    }
}
