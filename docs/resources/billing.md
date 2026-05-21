# Billing (`/v4/billing/...`)

Source: [`src/Resource/V4/BillingResource.php`](../../src/Resource/V4/BillingResource.php)

## Access

```php
$client->billing
```

`?string $organisationId = null` → scopes to `/self` when null.

## Methods

```php
public function getBalance(?string $organisationId = null): array<string, mixed>
public function listInvoices(?string $organisationId = null): list<Invoice>
public function getInvoice(string $invoiceNumber, ?string $organisationId = null): Invoice
public function downloadInvoicePdf(string $invoiceNumber, ?string $organisationId = null): ResponseInterface
public function paymentMethods(?string $organisationId = null): list<PaymentMethod>
public function addPaymentMethod(array $data, ?string $organisationId = null): PaymentMethod
public function removePaymentMethod(string $methodId, ?string $organisationId = null): void
public function consumptions(?string $organisationId = null, ?int $from = null, ?int $to = null): list<Consumption>
public function recurrent(?string $organisationId = null): array<string, mixed>
```

| Method | HTTP | Path |
| --- | --- | --- |
| `getBalance()` | GET | `/v4/billing/{self|organisations/{id}}/credits/balance` |
| `listInvoices()` | GET | `/v4/billing/.../invoices` |
| `getInvoice()` | GET | `/v4/billing/.../invoices/{invoiceNumber}` |
| `downloadInvoicePdf()` | GET | `/v4/billing/.../invoices/{invoiceNumber}.pdf` (returns raw PSR-7 `ResponseInterface` with `Accept: application/pdf`) |
| `paymentMethods()` | GET | `/v4/billing/.../payments/methods` |
| `addPaymentMethod()` | POST | `/v4/billing/.../payments/methods` (JSON body) |
| `removePaymentMethod()` | DELETE | `/v4/billing/.../payments/methods/{id}` |
| `consumptions()` | GET | `/v4/billing/.../consumptions` (query: `?from&to` ms since epoch) |
| `recurrent()` | GET | `/v4/billing/.../recurrent-payments` |

`downloadInvoicePdf()` returns a `Psr\Http\Message\ResponseInterface`
straight from the PSR-18 client — no JSON decoding. Stream `getBody()` to
disk.

## DTOs

- `Invoice` — [`src/Model/Invoice.php`](../../src/Model/Invoice.php)
- `PaymentMethod` — [`src/Model/PaymentMethod.php`](../../src/Model/PaymentMethod.php)
- `Consumption` — [`src/Model/Consumption.php`](../../src/Model/Consumption.php)
