<?php

/*
 * Authenticate the SDK with a Clever Cloud API token (Bearer) — the
 * recommended mode for non-interactive scripts.
 *
 * Mint a token from the Console (https://console.clever-cloud.com/) and
 * export it as CC_API_TOKEN before running:
 *
 *   export CC_API_TOKEN="cc_secret_..."
 *   php examples/api-token.php
 */

require __DIR__.'/../vendor/autoload.php';

use CleverCloud\Sdk\Auth\Credentials;
use CleverCloud\Sdk\ClientBuilder;

$token = getenv('CC_API_TOKEN');
if (false === $token || '' === $token) {
    fwrite(\STDERR, "Missing CC_API_TOKEN env var\n");
    exit(1);
}

$client = new ClientBuilder()
    ->withCredentials(Credentials::apiToken($token))
    ->build();

$me = $client->self->get();
printf("Logged in as %s (id=%s)\n", $me->email, $me->id);

// Bearer creds authorise the api-bridge endpoints — token CRUD lives there.
echo "\nExisting tokens:\n";
foreach ($client->apiTokens->list() as $apiToken) {
    printf(
        "  - %s — %s (created %s)\n",
        $apiToken->name,
        $apiToken->id,
        $apiToken->createdAt ?? 'unknown',
    );
}
