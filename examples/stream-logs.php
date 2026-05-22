<?php

/**
 * Streams live logs for a Clever Cloud application via /v4/logs.
 *
 * Usage:
 *   CC_API_TOKEN=cc_... php examples/stream-logs.php <app_id>
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

$apiToken = getenv('CC_API_TOKEN');
if (false === $apiToken || '' === $apiToken) {
    fwrite(\STDERR, "Missing env var: CC_API_TOKEN\n");
    exit(2);
}

$client = new ClientBuilder()
    ->withCredentials(Credentials::apiToken($apiToken))
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
