<?php

namespace CleverCloud\Sdk\Resource\V4;

use CleverCloud\Sdk\Model\Consumption;
use CleverCloud\Sdk\Model\Invoice;
use CleverCloud\Sdk\Model\PaymentMethod;
use CleverCloud\Sdk\Resource\AbstractV4Resource;
use Psr\Http\Message\ResponseInterface;

final readonly class BillingResource extends AbstractV4Resource
{
    /**
     * @return array<string, mixed>
     */
    public function getBalance(?string $organisationId = null): array
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet($this->billingPath($organisationId).'/credits/balance');

        return $payload;
    }

    /**
     * @return list<Invoice>
     */
    public function listInvoices(?string $organisationId = null): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->billingPath($organisationId).'/invoices');

        return $this->mapCollection(Invoice::class, $payload);
    }

    public function getInvoice(string $invoiceNumber, ?string $organisationId = null): Invoice
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet(
            $this->billingPath($organisationId).'/invoices/'.rawurlencode($invoiceNumber),
        );

        return $this->mapTo(Invoice::class, $payload);
    }

    /**
     * Returns the raw PDF/HTML invoice response so callers can stream the body
     * to disk or send it onward.
     */
    public function downloadInvoicePdf(string $invoiceNumber, ?string $organisationId = null): ResponseInterface
    {
        return $this->httpStream(
            'GET',
            $this->billingPath($organisationId).'/invoices/'.rawurlencode($invoiceNumber).'.pdf',
            ['headers' => ['Accept' => 'application/pdf']],
        );
    }

    /**
     * @return list<PaymentMethod>
     */
    public function paymentMethods(?string $organisationId = null): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet($this->billingPath($organisationId).'/payments/methods');

        return $this->mapCollection(PaymentMethod::class, $payload);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function addPaymentMethod(array $data, ?string $organisationId = null): PaymentMethod
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost(
            $this->billingPath($organisationId).'/payments/methods',
            ['json' => $data],
        );

        return $this->mapTo(PaymentMethod::class, $payload);
    }

    public function removePaymentMethod(string $methodId, ?string $organisationId = null): void
    {
        $this->httpDelete(
            $this->billingPath($organisationId).'/payments/methods/'.rawurlencode($methodId),
        );
    }

    /**
     * @return list<Consumption>
     */
    public function consumptions(?string $organisationId = null, ?int $from = null, ?int $to = null): array
    {
        $query = [];
        if (null !== $from) {
            $query['from'] = $from;
        }
        if (null !== $to) {
            $query['to'] = $to;
        }

        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet(
            $this->billingPath($organisationId).'/consumptions',
            ['query' => $query],
        );

        return $this->mapCollection(Consumption::class, $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function recurrent(?string $organisationId = null): array
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet($this->billingPath($organisationId).'/recurrent-payments');

        return $payload;
    }

    private function billingPath(?string $organisationId): string
    {
        return '/billing'.$this->ownerPath($organisationId);
    }
}
