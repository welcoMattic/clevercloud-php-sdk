<?php

/*
 * Demonstrates how to drive the SDK against an in-memory transport, with no
 * network IO. Useful for testing application code that depends on the SDK —
 * inject a MockHttpClient and assert what your code does with the responses.
 *
 * Run with:  php examples/mocking.php
 */

require __DIR__.'/../vendor/autoload.php';

use CleverCloud\Sdk\Auth\Credentials;
use CleverCloud\Sdk\ClientBuilder;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

// Queue up the responses the SDK is going to see, in order. MockHttpClient
// pops one per request and feeds it back.
$mock = new MockHttpClient([
    new MockResponse(
        json_encode([
            'id' => 'me_1',
            'email' => 'mock@example.com',
            'name' => 'Mock User',
        ], \JSON_THROW_ON_ERROR),
        ['response_headers' => ['content-type' => 'application/json']],
    ),
    new MockResponse(
        json_encode([
            ['id' => 'orga_1', 'name' => 'Acme Inc.'],
            ['id' => 'orga_2', 'name' => 'Beta Corp.'],
        ], \JSON_THROW_ON_ERROR),
        ['response_headers' => ['content-type' => 'application/json']],
    ),
]);

$client = new ClientBuilder()
    ->withCredentials(Credentials::apiToken('cc_mock_token'))
    ->withHttpClient($mock)
    ->build();

$me = $client->self->get();
printf("self.get()           -> id=%s email=%s\n", $me->id, $me->email);

$orgs = $client->organisations->list();
printf("organisations.list() -> %d orgs\n", \count($orgs));
foreach ($orgs as $org) {
    printf("  - %s (%s)\n", $org->name, $org->id);
}
