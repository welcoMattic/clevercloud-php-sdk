<?php

/**
 * End-to-end smoke test against the real /v2/self endpoint.
 *
 * Usage:
 *   CC_CONSUMER_KEY=... CC_CONSUMER_SECRET=... \
 *   CC_TOKEN=... CC_TOKEN_SECRET=... \
 *       php examples/smoke-self.php
 */

require __DIR__.'/../vendor/autoload.php';

use CleverCloud\Sdk\Auth\Credentials;
use CleverCloud\Sdk\ClientBuilder;
use CleverCloud\Sdk\Exception\CleverCloudException;

$required = ['CC_CONSUMER_KEY', 'CC_CONSUMER_SECRET', 'CC_TOKEN', 'CC_TOKEN_SECRET'];
foreach ($required as $name) {
    if (false === getenv($name) || '' === getenv($name)) {
        fwrite(\STDERR, "Missing env var: {$name}\n");
        exit(2);
    }
}

$client = new ClientBuilder()
    ->withCredentials(new Credentials(
        consumerKey: (string) getenv('CC_CONSUMER_KEY'),
        consumerSecret: (string) getenv('CC_CONSUMER_SECRET'),
        token: (string) getenv('CC_TOKEN'),
        tokenSecret: (string) getenv('CC_TOKEN_SECRET'),
    ))
    ->build();

try {
    $me = $client->self->get();
} catch (CleverCloudException $e) {
    fwrite(\STDERR, \sprintf("Error: %s (%s)\n", $e->getMessage(), $e::class));
    exit(1);
}

printf("id:    %s\n", $me['id'] ?? '(unknown)');
printf("email: %s\n", $me['email'] ?? '(unknown)');
printf("name:  %s %s\n", $me['firstname'] ?? '', $me['lastname'] ?? '');
