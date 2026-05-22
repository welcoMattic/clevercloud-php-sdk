<?php

/**
 * End-to-end smoke test against the real /v2/self endpoint.
 *
 * Usage:
 *   CC_API_TOKEN=cc_... php examples/smoke-self.php
 */

require __DIR__.'/../vendor/autoload.php';

use CleverCloud\Sdk\Auth\Credentials;
use CleverCloud\Sdk\ClientBuilder;
use CleverCloud\Sdk\Exception\CleverCloudException;

$apiToken = getenv('CC_API_TOKEN');
if (false === $apiToken || '' === $apiToken) {
    fwrite(\STDERR, "Missing env var: CC_API_TOKEN\n");
    exit(2);
}

$client = new ClientBuilder()
    ->withCredentials(Credentials::apiToken($apiToken))
    ->build();

try {
    $me = $client->self->get();
} catch (CleverCloudException $e) {
    fwrite(\STDERR, \sprintf("Error: %s (%s)\n", $e->getMessage(), $e::class));
    exit(1);
}

printf("id:    %s\n", $me->id);
printf("email: %s\n", $me->email ?? '(unknown)');
printf("name:  %s %s\n", $me->firstname ?? '', $me->lastname ?? '');
