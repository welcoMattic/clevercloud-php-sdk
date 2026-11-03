# Network Groups (`/v4/networkgroups/...`)

Source: [`src/Resource/V4/NetworkGroupsResource.php`](https://github.com/welcoMattic/clevercloud-php-sdk/blob/main/src/Resource/V4/NetworkGroupsResource.php)

WireGuard-based private networks linking applications, add-ons, load
balancers and external peers together.

## Access

```php
$client->networkGroups
```

`?string $organisationId = null` scopes to `/self`; pass an organisation ID
to scope to `/organisations/{id}`. Paths below write that prefix as `...`.

## Platform configuration

```php
public function configuration(): NetworkGroupsConfiguration
```

`GET /networkgroup/configuration`, served at the API root (outside `/v4`).
Returns `NetworkGroupsConfiguration::$dnsSuffixes`: a member `domainName` must
end with a dot followed by one of them. The endpoint needs no authentication;
the SDK signs the request anyway.

## Network groups

```php
public function list(?string $organisationId = null, array $tags = []): list<NetworkGroup>
public function search(string $query, ?string $organisationId = null): list<NetworkGroup|NetworkGroupMember|NetworkGroupPeer>
public function get(string $networkGroupId, ?string $organisationId = null): NetworkGroup
public function create(string $label, ?string $description = null, ?string $organisationId = null, ?string $id = null, array $tags = [], array $members = []): void
public function delete(string $networkGroupId, ?string $organisationId = null): void
```

| Method | HTTP | Path | Body |
| --- | --- | --- | --- |
| `list()` | GET | `/v4/networkgroups/.../networkgroups?tags=...` | - |
| `search()` | GET | `/v4/networkgroups/.../networkgroups/search?query=...` | - |
| `get()` | GET | `/v4/networkgroups/.../networkgroups/{id}` | - |
| `create()` | POST | `/v4/networkgroups/.../networkgroups` | `{"label", "description"?, "id"?, "tags"?, "members"?}` |
| `delete()` | DELETE | `/v4/networkgroups/.../networkgroups/{id}` | - |

`list()` repeats `tags` in the query string and keeps only the groups that
carry every given tag.

`search()` runs a case-insensitive substring search over the IDs, labels and
tags of the groups and the IDs and labels of their members and peers. Each hit
is typed after its `type` discriminator: `NetworkGroup`, `Member`
(`NetworkGroupMember`), `CleverPeer` or `ExternalPeer` (`NetworkGroupPeer`).
Hits of an unknown type are skipped.

`create()` returns `void` since 2.1; it used to declare a `NetworkGroup`
return type. `create()` and `delete()` are asynchronous: the API answers
`202` / `204` with an empty body, and a new group cannot be read until it
exists. The former `create()` tried to map that empty body into a
`NetworkGroup` and always threw. Pass `$id` to
know the ID of the group up front. `$members` entries carry the same fields as
`addMember()`: `list<array{id: string, kind: string, label?: string, domainName?: string}>`.

`NetworkGroup` carries `label`, `description`, `ownerId`, `networkIp` (the
CIDR block), `lastAllocatedIp`, `dnsSanitizedLabel`, `ownership`
(`customer` or `platform`), `tags`, `version`, `members` and `peers`.

### Watching a group

```php
public function stream(string $networkGroupId, ?string $organisationId = null, ?int $maxDurationSeconds = null): NetworkGroupStream
```

`GET /v4/networkgroups/.../networkgroups/{id}/stream` is a Server-Sent Events
stream. `NetworkGroupStream` yields a typed `NetworkGroup` every time the
group version changes (checked every 15 seconds by the API) and skips
`HEARTBEAT` events. The stream ends when the group disappears; pass
`$maxDurationSeconds` to stop earlier.

```php
foreach ($client->networkGroups->stream('ng_...', 'orga_...', maxDurationSeconds: 60) as $networkGroup) {
    echo $networkGroup->version, PHP_EOL;
}
```

## Members

```php
public function members(string $networkGroupId, ?string $organisationId = null): list<NetworkGroupMember>
public function member(string $networkGroupId, string $memberId, ?string $organisationId = null): NetworkGroupMember
public function addMember(string $networkGroupId, string $memberId, string $kind, ?string $label = null, ?string $organisationId = null, ?string $domainName = null): void
public function removeMember(string $networkGroupId, string $memberId, ?string $organisationId = null): void
```

| Method | HTTP | Path | Body |
| --- | --- | --- | --- |
| `members()` | GET | `/v4/networkgroups/.../networkgroups/{id}/members` | - |
| `member()` | GET | `/v4/networkgroups/.../networkgroups/{id}/members/{memberId}` | - |
| `addMember()` | POST | `/v4/networkgroups/.../networkgroups/{id}/members` | `{"id", "kind", "label"?, "domainName"?}` |
| `removeMember()` | DELETE | `/v4/networkgroups/.../networkgroups/{id}/members/{memberId}` | - |

`$kind` is `APPLICATION`, `ADDON`, `EXTERNAL` or `LOADBALANCER`, in any case;
the API returns it in lowercase. When `$domainName` is null the API derives
`<member>.m.<network-group><suffix>`; a given value must end with one of the
`configuration()` DNS suffixes. Additions and removals are asynchronous.

## Peers

```php
public function peers(string $networkGroupId, ?string $organisationId = null): list<NetworkGroupPeer>
public function peer(string $networkGroupId, string $peerId, ?string $organisationId = null): NetworkGroupPeer
public function addPeer(string $networkGroupId, string $peerId, string $peerRole, string $peerKind, string $parentMember, ?string $label = null, ?string $publicKey = null, ?string $hostname = null, ?string $ip = null, ?int $port = null, ?string $hv = null, ?string $parentEvent = null, ?string $organisationId = null): string
public function removePeer(string $networkGroupId, string $peerId, ?string $organisationId = null): void
```

| Method | HTTP | Path | Body |
| --- | --- | --- | --- |
| `peers()` | GET | `/v4/networkgroups/.../networkgroups/{id}/peers` | - |
| `peer()` | GET | `/v4/networkgroups/.../networkgroups/{id}/peers/{peerId}` | - |
| `addPeer()` | POST | `/v4/networkgroups/.../networkgroups/{id}/peers` | `{"id", "peerRole", "peerKind", "parentMember", ...}` |
| `removePeer()` | DELETE | `/v4/networkgroups/.../networkgroups/{id}/peers/{peerId}` | - |

`$peerRole` is `CLIENT` or `SERVER`, `$peerKind` is `CLEVER` or `EXTERNAL`. A
`SERVER` peer needs `$ip` and `$port`, an external peer its `$publicKey`, a
Clever Cloud peer its hypervisor `$hv`. `addPeer()` returns the peer ID from
the `201` answer; the peer itself is created asynchronously. `removePeer()`
only removes external peers: a Clever Cloud instance peer leaves the group
with its instance or its member.

`NetworkGroupPeer` covers both peer types, told apart by `type` (`CleverPeer`
or `ExternalPeer`): `id`, `label`, `publicKey`, `hostname`, `parentMember`,
`parentEvent`, `hv` (Clever Cloud peers only), `routedPrefixes` and
`endpoint`. `NetworkGroupPeerEndpoint` is a `ClientEndpoint` (mesh address in
`ngIp`) or a `ServerEndpoint` (`ngTerm` and `publicTerm`, both
`NetworkGroupSocketAddress` with `host` and `port`).

## External peers

```php
public function addExternalPeer(string $networkGroupId, string $label, string $peerRole, string $publicKey, string $parentMember, ?string $hostname = null, ?string $ip = null, ?int $port = null, ?string $parentEvent = null, ?string $organisationId = null): string
public function externalPeer(string $networkGroupId, string $peerId, ?string $organisationId = null): NetworkGroupPeer
public function removeExternalPeer(string $networkGroupId, string $peerId, ?string $organisationId = null): void
```

| Method | HTTP | Path | Body |
| --- | --- | --- | --- |
| `addExternalPeer()` | POST | `/v4/networkgroups/.../networkgroups/{id}/external-peers` | `{"label", "peerRole", "publicKey", "parentMember", ...}` |
| `externalPeer()` | GET | `/v4/networkgroups/.../networkgroups/{id}/external-peers/{peerId}` | - |
| `removeExternalPeer()` | DELETE | `/v4/networkgroups/.../networkgroups/{id}/external-peers/{peerId}` | - |

`addExternalPeer()` lets the API generate the peer ID and returns it.

## WireGuard configuration

```php
public function wireguardConfiguration(string $networkGroupId, string $peerId, ?string $organisationId = null): string
public function wireguardConfigurationPresignedUrl(string $networkGroupId, string $peerId, ?string $organisationId = null): string
public function streamWireguardConfiguration(string $networkGroupId, string $peerId, ?string $organisationId = null, ?int $maxDurationSeconds = null): EventStream
```

| Method | HTTP | Path |
| --- | --- | --- |
| `wireguardConfiguration()` | GET | `/v4/networkgroups/.../networkgroups/{id}/peers/{peerId}/wireguard/configuration` |
| `wireguardConfigurationPresignedUrl()` | GET | `.../peers/{peerId}/wireguard/configuration/presigned-url` |
| `streamWireguardConfiguration()` | GET | `.../peers/{peerId}/wireguard/configuration/stream` |

`wireguardConfiguration()` returns the INI text of the peer: its `[Interface]`
section and one `[Peer]` section per other peer with a valid public key. Use
it to provision an external peer (laptop, on-premises server).

`wireguardConfigurationPresignedUrl()` returns a URL that downloads the same
configuration with no other credential, valid 60 minutes. Keep it secret: its
token grants that download for every peer of the organisation's Network
Groups until it expires. Biscuit credentials cannot obtain one.

`streamWireguardConfiguration()` is a Server-Sent Events stream pushing the
configuration every time the group version changes. The API documents each
event as `WireGuardConfigurationB64` (the INI text, base64-encoded) without
publishing its fields, so `EventStream` yields every event as a `StreamEvent`
whose `data` is the decoded JSON object (`array<string, mixed>`), and leaves
the base64 decoding to the caller. `HEARTBEAT` events are skipped.
