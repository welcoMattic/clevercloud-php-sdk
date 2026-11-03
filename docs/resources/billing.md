# Billing (`/v4/billing/...`)

Source: [`src/Resource/V4/BillingResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V4/BillingResource.php)

## Access

```php
$client->billing
```

Paths are scoped to `/organisations/{id}`. A null `?string $organisationId`
targets your personal space, whose `user_...` id is read from `GET /v2/self`
first (V4 has no `/self` variant). Paths below write that prefix as `...`.

## Methods

```php
public function getBalance(?string $organisationId = null): array<string, mixed>
public function listInvoices(?string $organisationId = null): list<Invoice>
public function getInvoice(string $invoiceNumber, ?string $organisationId = null): Invoice
public function downloadInvoicePdf(string $invoiceNumber, ?string $organisationId = null): ResponseInterface
public function paymentMethods(?string $organisationId = null): list<PaymentMethod>
public function addPaymentMethod(array $data, ?string $organisationId = null): PaymentMethod
public function removePaymentMethod(string $methodId, ?string $organisationId = null): void
```

| Method | HTTP | Path |
| --- | --- | --- |
| `getBalance()` | GET | `/v4/billing/.../credits/summary` (`prepaidCredit`, `freeCredit`, `currency`) |
| `listInvoices()` | GET | `/v4/billing/.../invoices` |
| `getInvoice()` | GET | `/v4/billing/.../invoices/{invoiceNumber}` |
| `downloadInvoicePdf()` | GET | `/v4/billing/.../invoices/{invoiceNumber}.pdf` (returns raw PSR-7 `ResponseInterface` with `Accept: application/pdf`) |
| `paymentMethods()` | GET | `/v4/billing/.../payments/methods` |
| `addPaymentMethod()` | POST | `/v4/billing/.../payments/methods` (JSON body) |
| `removePaymentMethod()` | DELETE | `/v4/billing/.../payments/methods/{id}` |

`downloadInvoicePdf()` returns a `Psr\Http\Message\ResponseInterface`
straight from the PSR-18 client — no JSON decoding. Stream `getBody()` to
disk.

## DTOs

- `Invoice` — [`src/Model/Invoice.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Model/Invoice.php)
- `PaymentMethod` — [`src/Model/PaymentMethod.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Model/PaymentMethod.php)
