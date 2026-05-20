<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V4;

use CleverCloud\Sdk\Resource\V4\BillingResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(BillingResource::class)]
final class BillingResourceTest extends TestCase
{
    public function testListInvoicesHitsSelfPath(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
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
        ]);

        $invoices = $this->resource($response)->listInvoices();

        self::assertCount(2, $invoices);
        self::assertSame('INV-001', $invoices[0]->invoiceNumber);
        self::assertSame('PAID', $invoices[0]->status);
        self::assertSame('120.00', $invoices[0]->totalTaxIncluded);
        self::assertSame('PENDING', $invoices[1]->status);
        self::assertSame('https://api.clever-cloud.com/v4/billing/self/invoices', $response->getRequestUrl());
    }

    public function testListInvoicesForOrganisationRoutesUnderOrg(): void
    {
        $response = ResourceFactory::jsonResponse(200, []);

        $this->resource($response)->listInvoices('orga_xyz');

        self::assertSame(
            'https://api.clever-cloud.com/v4/billing/organisations/orga_xyz/invoices',
            $response->getRequestUrl(),
        );
    }

    public function testGetBalanceHitsCreditsBalance(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'currency' => 'EUR',
            'balance' => 42.5,
            'remainingPrepaid' => 12.75,
        ]);

        $balance = $this->resource($response)->getBalance('orga_1');

        self::assertSame(['currency' => 'EUR', 'balance' => 42.5, 'remainingPrepaid' => 12.75], $balance);
        self::assertSame(
            'https://api.clever-cloud.com/v4/billing/organisations/orga_1/credits/balance',
            $response->getRequestUrl(),
        );
    }

    public function testRemovePaymentMethodHitsDelete(): void
    {
        $response = ResourceFactory::jsonResponse(204, []);

        $this->resource($response)->removePaymentMethod('pm_xyz');

        self::assertSame('DELETE', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v4/billing/self/payments/methods/pm_xyz',
            $response->getRequestUrl(),
        );
    }

    public function testConsumptionsAppendsFromToQuery(): void
    {
        $response = ResourceFactory::jsonResponse(200, []);

        $this->resource($response)->consumptions('orga_1', from: 1_700_000_000_000, to: 1_710_000_000_000);

        self::assertSame(
            'https://api.clever-cloud.com/v4/billing/organisations/orga_1/consumptions?from=1700000000000&to=1710000000000',
            $response->getRequestUrl(),
        );
    }

    private function resource(MockResponse $response): BillingResource
    {
        return new BillingResource(
            ResourceFactory::http(new MockHttpClient([$response])),
            ResourceFactory::mapper(),
        );
    }
}
