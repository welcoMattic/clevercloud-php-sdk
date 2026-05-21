# Products catalogue (`/v2/products/...`)

Source: [`src/Resource/V2/ProductsResource.php`](../../src/Resource/V2/ProductsResource.php)

The platform-wide catalogue of instance types, add-on providers, zones,
and countries. Read-only, no auth scope required.

## Access

```php
$client->products
```

## Methods

```php
public function instances(): list<InstanceType>
public function addonProviders(): list<AddonProvider>
public function zones(): list<Zone>
public function countries(): list<Country>
```

| Method | HTTP | Path |
| --- | --- | --- |
| `instances()` | GET | `/v2/products/instances` |
| `addonProviders()` | GET | `/v2/products/addonproviders` |
| `zones()` | GET | `/v2/products/zones` |
| `countries()` | GET | `/v2/products/countries` |

These return live data from Clever Cloud — they pick up new instance
runtimes, zones, and add-on providers automatically. Use them to populate
form selects, validate user input against platform-supported values, etc.

## When to use API calls vs PHP enums

| Reason | Use |
| --- | --- |
| Stable platform-wide constants (Flavor, DeployType, ApplicationState, MigrationStatus, MemberRole, DrainType, WebhookFormat, DeploymentAction, DeploymentState) | PHP enums under `CleverCloud\Sdk\Model\Enum\` |
| Lists that Clever Cloud updates faster than SDK releases (zones, instance types, add-on providers, countries) | These products methods |
