<?php

/**
 * Streams live logs for a Clever Cloud application via /v4/logs.
 *
 * Usage:
 *   CC_CONSUMER_KEY=... CC_CONSUMER_SECRET=... \
 *   CC_TOKEN=... CC_TOKEN_SECRET=... \
 *       php examples/stream-logs.php <app_id>
 */

require __DIR__.'/../vendor/autoload.php';

use CleverCloud\Sdk\Auth\Credentials;
use CleverCloud\Sdk\ClientBuilder;
use CleverCloud\Sdk\Exception\CleverCloudException;

if ($argc < 2) {
    fwrite(\STDERR, "Usage: php examples/stream-logs.php <app_id>\n");
    exit(2);
}

$appId = $argv[1];

$required = ['CC_CONSUMER_KEY', 'CC_CONSUMER_SECRET', 'CC_TOKEN', 'CC_TOKEN_SECRET'];
foreach ($required as $name) {
    if (false === getenv($name) || '' === getenv($name)) {
        fwrite(\STDERR, "Missing env var: {$name}\n");
        exit(2);
    }
}

$client = new ClientBuilder()
    ->withCredentials(Credentials::oauth1(
        consumerKey: (string) getenv('CC_CONSUMER_KEY'),
        consumerSecret: (string) getenv('CC_CONSUMER_SECRET'),
        token: (string) getenv('CC_TOKEN'),
        tokenSecret: (string) getenv('CC_TOKEN_SECRET'),
    ))
    ->build();

try {
    $deadline = time() + 10;
    foreach ($client->logs->stream($appId) as $entry) {
        printf("[%s] %s\n", $entry->severity ?? 'INFO', $entry->message);
        if (time() >= $deadline) {
            break;
        }
    }
} catch (CleverCloudException $e) {
    fwrite(\STDERR, \sprintf("Error: %s (%s)\n", $e->getMessage(), $e::class));
    exit(1);
}
