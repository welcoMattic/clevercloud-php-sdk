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
printf("Name:      %s\n", $me->name ?? '(not set)');

printf("\nOrganisations reachable with this token: %d\n", \count($client->organisations->list()));
foreach ($client->organisations->list() as $organisation) {
    printf("  - %s (%s)\n", $organisation->name, $organisation->id);
}

// Deliberately NOT calling $client->apiTokens here. Token CRUD lives on
// api-bridge.clever-cloud.com, and that gateway only accepts OAuth 1.0a
// signatures: it rejects a Bearer header outright with
// `400 must start with "OAuth "`. Minting a token is what you use OAuth1 FOR,
// so a token-authenticated client cannot manage tokens. Use OAuth 1.0a
// credentials for that; see examples/stream-logs-oauth.php.
