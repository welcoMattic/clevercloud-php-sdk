# Pulsar topics and clusters (`/v4/addon-providers/addon-pulsar`)

Source: [`src/Resource/V4/PulsarResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V4/PulsarResource.php)

Manages the topics of a Pulsar add-on and reads Pulsar clusters. Storage
policies have their own resource: [`$client->pulsarPolicies`](pulsar-policies.md).

## Access

```php
$client->pulsar
```

`$pulsarId` is the real add-on id (`pulsar_xxx`).

## Methods

```php
public function topics(string $pulsarId, bool $persistent = true): list<PulsarTopic>
public function createTopic(string $pulsarId, string $topic, bool $persistent = true): void
public function deleteTopic(string $pulsarId, string $topic, bool $persistent = true): void
public function createTopicToken(string $pulsarId, string $topic, bool $persistent = true): string
public function unloadTopic(string $pulsarId, string $topic, bool $persistent = true): void
public function cluster(string $clusterId): PulsarCluster
```

Topic methods target persistent topics by default. Pass `persistent: false`
to target non-persistent topics: the `{kind}` segment below is then
`non-persistent-topics` instead of `topics`.

| Method | HTTP | Path |
| --- | --- | --- |
| `topics()` | GET | `/v4/addon-providers/addon-pulsar/addons/{pulsarId}/{kind}` |
| `createTopic()` | POST | `/v4/addon-providers/addon-pulsar/addons/{pulsarId}/{kind}/{topic}` (no body) |
| `deleteTopic()` | DELETE | `/v4/addon-providers/addon-pulsar/addons/{pulsarId}/{kind}/{topic}` |
| `createTopicToken()` | POST | `/v4/addon-providers/addon-pulsar/addons/{pulsarId}/{kind}/{topic}/token` |
| `unloadTopic()` | PUT | `/v4/addon-providers/addon-pulsar/addons/{pulsarId}/{kind}/{topic}/unload` |
| `cluster()` | GET | `/v4/addon-providers/addon-pulsar/clusters/{clusterId}` |

`createTopicToken()` returns a new Biscuit access token (plain text)
restricted to that topic, to hand to a producer or consumer that must reach
this topic only.

```php
$client->pulsar->createTopic('pulsar_xxx', 'orders');
$token = $client->pulsar->createTopicToken('pulsar_xxx', 'orders');

$client->pulsar->createTopic('pulsar_xxx', 'events', persistent: false);
```

## DTOs

- `PulsarTopic`: `name` (full topic name, e.g. `persistent://tenant/ns/topic`).
- `PulsarCluster`: `id`, `url`, `version`, `zone`, `available`, `pulsarPort`,
  `pulsarTlsPort`, `webPort`, `webTlsPort`, `adminHost`,
  `supportColdStorage`, `supportedPlans`.
