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
    public function testListInvoicesResolvesThePersonalOrganisation(): void
    {
        $self = ResourceFactory::jsonResponse(200, ['id' => 'user_1']);
        $response = ResourceFactory::jsonResponse(200, [
            [
                'invoice_number' => 'INV-001',
                'kind' => 'INVOICE',
                'owner_id' => 'user_1',
                'emission_date' => '2026-11-02T12:36:43.131194Z',
                'pay_date' => null,
                'status' => 'PAID',
                'currency' => 'EUR',
                'vat_percent' => 20.0,
                'discount' => 0.0,
                'total_tax_excluded' => ['currency' => 'EUR', 'amount' => 38.82, 'amount_formatted' => '38.82', 'default_display' => '€38.82'],
                'total_tax' => ['currency' => 'EUR', 'amount' => 7.76, 'amount_formatted' => '7.76', 'default_display' => '€7.76'],
                'category' => 'PAAS',
            ],
            [
                'invoice_number' => 'INV-002',
                'status' => 'PENDING',
            ],
        ]);

        $invoices = $this->resource($self, $response)->listInvoices();

        self::assertCount(2, $invoices);
        self::assertSame('INV-001', $invoices[0]->invoiceNumber);
        self::assertSame('PAID', $invoices[0]->status);
        self::assertSame('2026-11-02T12:36:43.131194Z', $invoices[0]->emissionDate);
        self::assertSame(38.82, $invoices[0]->totalTaxExcluded?->amount);
        self::assertSame('€7.76', $invoices[0]->totalTax?->defaultDisplay);
        self::assertSame(20.0, $invoices[0]->vatPercent);
        self::assertSame('user_1', $invoices[0]->ownerId);
        self::assertSame('PENDING', $invoices[1]->status);
        self::assertSame('https://api.clever-cloud.com/v2/self', $self->getRequestUrl());
        self::assertSame('https://api.clever-cloud.com/v4/billing/organisations/user_1/invoices', $response->getRequestUrl());
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

    public function testGetBalanceReadsTheCreditsSummary(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'prepaidCredit' => 0,
            'freeCredit' => 30.19,
            'currency' => 'EUR',
        ]);

        $balance = $this->resource($response)->getBalance('orga_1');

        self::assertSame(['prepaidCredit' => 0, 'freeCredit' => 30.19, 'currency' => 'EUR'], $balance);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v4/billing/organisations/orga_1/credits/summary',
            $response->getRequestUrl(),
        );
    }

    public function testRemovePaymentMethodHitsDelete(): void
    {
        $self = ResourceFactory::jsonResponse(200, ['id' => 'user_1']);
        $response = ResourceFactory::jsonResponse(204, []);

        $this->resource($self, $response)->removePaymentMethod('pm_xyz');

        self::assertSame('DELETE', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v4/billing/organisations/user_1/payments/methods/pm_xyz',
            $response->getRequestUrl(),
        );
    }

    private function resource(MockResponse ...$responses): BillingResource
    {
        return new BillingResource(
            ResourceFactory::http(new MockHttpClient($responses)),
            ResourceFactory::mapper(),
        );
    }
}
