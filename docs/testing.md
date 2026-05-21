# Testing your code against the SDK

The SDK's transport is `symfony/http-client`. Inject Symfony's `MockHttpClient`
through `ClientBuilder::withHttpClient()` and you get full control over what
each call returns — no network IO, fully deterministic.

## Minimal example

```php
use CleverCloud\Sdk\Auth\Credentials;
use CleverCloud\Sdk\ClientBuilder;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

$mock = new MockHttpClient([
    new MockResponse(
        json_encode(['id' => 'app_42', 'name' => 'hello']),
        ['response_headers' => ['content-type' => 'application/json']],
    ),
]);

$client = (new ClientBuilder())
    ->withCredentials(Credentials::apiToken('test'))
    ->withHttpClient($mock)
    ->build();

$app = $client->applications->get('app_42');
assert($app->name === 'hello');
```

`MockHttpClient` queues responses — each request the SDK makes pops the next
one. Pass a `Closure` instead of an array to inspect the URL/method and
respond dynamically.

A runnable variant lives at [`examples/mocking.php`](../examples/mocking.php).

## Inspecting what the SDK sent

After the call, `MockResponse` exposes the request that produced it:

```php
$response = new MockResponse(
    '{"id": "app_42"}',
    ['response_headers' => ['content-type' => 'application/json']],
);

$client->applications->restart('app_42', 'orga_1', withoutCache: true);

$response->getRequestMethod();   // 'POST'
$response->getRequestUrl();      // 'https://api.clever-cloud.com/v2/organisations/orga_1/applications/app_42/instances?useCache=no'
$response->getRequestOptions();  // ['headers' => [...], 'body' => '{}', ...]
```

The SDK's own test suite uses this exact pattern — see
[`tests/Unit/Resource/V2/ApplicationsResourceTest.php`](../tests/Unit/Resource/V2/ApplicationsResourceTest.php)
or any other `*ResourceTest.php`.

## Dynamic response callback

When you need a response that depends on the URL, pass a callable:

```php
$mock = new MockHttpClient(function (string $method, string $url, array $options) {
    if (str_ends_with($url, '/v2/self')) {
        return new MockResponse(json_encode(['id' => 'me_1', 'email' => 'me@example.com']));
    }
    return new MockResponse('{"error": "unexpected URL"}', ['http_code' => 404]);
});
```

## Asserting headers and body

The SDK ships a small helper for header assertions —
`tests/Unit/Fixture/ResourceFactory::headers()` and `findHeader()`. If you
want to use the same pattern in your own tests, replicate this snippet:

```php
function findHeader(MockResponse $r, string $name): ?string {
    foreach ($r->getRequestOptions()['headers'] ?? [] as $line) {
        if (\is_string($line) && str_starts_with($line, $name.':')) {
            return $line;
        }
    }
    return null;
}

// Usage:
$auth = findHeader($response, 'Authorization');
assert(str_starts_with($auth, 'Authorization: Bearer '));
```

Verified against the helpers in
[`tests/Unit/Fixture/ResourceFactory.php`](../tests/Unit/Fixture/ResourceFactory.php).

## Pinning the OAuth1 signer for deterministic signatures

If your tests sign OAuth1 requests and compare them to fixtures:

```php
use Symfony\Component\Clock\MockClock;
use CleverCloud\Sdk\Auth\NonceGenerator;

final class FixedNonce implements NonceGenerator {
    public function __construct(private string $value) {}
    public function generate(): string { return $this->value; }
}

$client = (new ClientBuilder())
    ->withCredentials(Credentials::oauth1('ck', 'cs', 'tk', 'ts'))
    ->withHttpClient($mock)
    ->withClock(new MockClock('@1700000000'))
    ->withNonceGenerator(new FixedNonce('test-nonce'))
    ->build();
```

## Testing the SSE log stream

`LogsResource::stream()` opens a Symfony SSE connection. `MockHttpClient`
returns SSE frames the same way regular responses work:

```php
$frame1 = json_encode(['message' => 'hello', 'instance_id' => 'i_1']);
$frame2 = json_encode(['message' => 'world', 'instance_id' => 'i_2']);

$response = new MockResponse(
    ['data: '.$frame1."\n\n", 'data: '.$frame2."\n\n"],
    ['response_headers' => ['content-type' => 'text/event-stream']],
);

$client = (new ClientBuilder())
    ->withCredentials(Credentials::apiToken('test'))
    ->withHttpClient(new MockHttpClient([$response]))
    ->build();

$entries = iterator_to_array($client->logs->stream('app_42', 'orga_1'), false);
assert(count($entries) === 2);
assert($entries[0]->message === 'hello');
```

See [`tests/Unit/Resource/V4/LogsResourceTest.php`](../tests/Unit/Resource/V4/LogsResourceTest.php).

## Asserting an exception is raised

```php
use CleverCloud\Sdk\Exception\ValidationException;

$mock = new MockHttpClient([
    new MockResponse(
        json_encode([
            'message' => 'invalid input',
            'errors' => ['name' => ['must not be blank', 'too short']],
        ]),
        ['http_code' => 422, 'response_headers' => ['content-type' => 'application/json']],
    ),
]);

try {
    $client->applications->create(['name' => '']);
    fail('expected ValidationException');
} catch (ValidationException $e) {
    assert($e->statusCode === 422);
    assert($e->errors['name'] === ['must not be blank', 'too short']);
}
```
