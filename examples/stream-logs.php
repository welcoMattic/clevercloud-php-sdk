<?php

/**
 * Streams live logs for a Clever Cloud application.
 *
 * IMPORTANT: The logs endpoint only works with OAuth 1.0a credentials.
 * API tokens (Bearer) are NOT supported for logs by Clever Cloud's API.
 *
 * Usage with OAuth 1.0a:
 *   CC_CONSUMER_KEY=xxx CC_CONSUMER_SECRET=yyy CC_TOKEN=zzz CC_TOKEN_SECRET=www \
 *     php examples/stream-logs.php <app_id> [owner_id]
 *
 * Note: owner_id is optional. Use it only if the app belongs to an organisation.
 *       If omitted, /self will be used (your personal applications).
 */

require __DIR__.'/../vendor/autoload.php';

use CleverCloud\Sdk\Auth\Credentials;
use CleverCloud\Sdk\ClientBuilder;
use CleverCloud\Sdk\Exception\CleverCloudException;

if ($argc < 2) {
    fwrite(\STDERR, "Usage: php examples/stream-logs.php <app_id> [owner_id]\n");
    exit(2);
}

$appId = $argv[1];
$ownerId = $argc > 2 ? $argv[2] : null;

$consumerKey = getenv('CC_CONSUMER_KEY');
$consumerSecret = getenv('CC_CONSUMER_SECRET');
$userToken = getenv('CC_TOKEN');
$userTokenSecret = getenv('CC_TOKEN_SECRET');

if (false === $consumerKey || '' === $consumerKey ||
    false === $consumerSecret || '' === $consumerSecret) {
    fwrite(\STDERR, "Missing env vars: CC_CONSUMER_KEY, CC_CONSUMER_SECRET\n");
    exit(2);
}

$client = new ClientBuilder()
    ->withCredentials(Credentials::oauth1(
        consumerKey: $consumerKey,
        consumerSecret: $consumerSecret,
        token: $userToken ?? null,
        tokenSecret: $userTokenSecret ?? null,
    ))
    ->build();

try {
    $deadline = time() + 10;
    foreach ($client->logs->stream($appId, $ownerId) as $entry) {
        printf("[%s] %s\n", $entry->severity ?? 'INFO', $entry->message);
        if (time() >= $deadline) {
            break;
        }
    }
} catch (CleverCloudException $e) {
    fwrite(\STDERR, \sprintf("Error: %s (%s)\n", $e->getMessage(), $e::class));
    exit(1);
}
