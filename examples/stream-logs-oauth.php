<?php

/**
 * Streams live logs for a Clever Cloud application using OAuth 1.0a.
 *
 * This example handles the full OAuth 1.0a flow automatically:
 * 1. It starts the OAuth flow if no tokens are cached
 * 2. It opens the authorization URL in your browser
 * 3. After you authorize, it continues and streams logs
 *
 * Usage:
 *   CC_CONSUMER_KEY=xxx CC_CONSUMER_SECRET=yyy php examples/stream-logs-oauth.php <app_id> [owner_id]
 *
 * The first time you run it, it will open your browser for authorization.
 * Subsequent runs will use cached tokens (stored in ~/.clevercloud-php-sdk-tokens).
 *
 * Note: owner_id is optional. Use it only if the app belongs to an organisation.
 */

require __DIR__.'/../vendor/autoload.php';

use CleverCloud\Sdk\Auth\Credentials;
use CleverCloud\Sdk\Auth\OAuthFlow;
use CleverCloud\Sdk\Auth\OAuth1Signer;
use CleverCloud\Sdk\ClientBuilder;
use CleverCloud\Sdk\Configuration;
use CleverCloud\Sdk\Exception\CleverCloudException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\Psr18Client;

if ($argc < 2) {
    fwrite(\STDERR, "Usage: php examples/stream-logs-oauth.php <app_id> [owner_id]\n");
    exit(2);
}

$appId = $argv[1];
$ownerId = $argc > 2 ? $argv[2] : null;

$consumerKey = getenv('CC_CONSUMER_KEY');
$consumerSecret = getenv('CC_CONSUMER_SECRET');

if (false === $consumerKey || '' === $consumerKey ||
    false === $consumerSecret || '' === $consumerSecret) {
    fwrite(\STDERR, "Missing env vars: CC_CONSUMER_KEY, CC_CONSUMER_SECRET\n");
    exit(2);
}

// Token cache file
$tokenCacheFile = getenv('HOME').'/.clevercloud-php-sdk-tokens';

// Load cached tokens if they exist
$userToken = null;
$userTokenSecret = null;
if (file_exists($tokenCacheFile)) {
    $cached = json_decode(file_get_contents($tokenCacheFile), true);
    if (isset($cached['token'], $cached['token_secret'])) {
        $userToken = $cached['token'];
        $userTokenSecret = $cached['token_secret'];
    }
}

$psr17Factory = new Psr17Factory();
$psr18Client = new Psr18Client();
$signer = new OAuth1Signer();
$flow = new OAuthFlow(
    signer: $signer,
    psr18: $psr18Client,
    requestFactory: $psr17Factory,
    configuration: new Configuration(),
);

// If we don't have tokens, start the OAuth flow
if (null === $userToken || null === $userTokenSecret) {
    echo "Starting OAuth flow...\n";

    try {
        // Step 1: Get request token
        // Use 'oob' for CLI apps (out-of-band)
        $requestToken = $flow->requestToken($consumerKey, $consumerSecret, 'oob');
        echo "Request token received.\n";

        // Step 2: Get authorization URL
        $authorizeUrl = $flow->authorizationUrl($requestToken['token']);
        echo "Please open this URL in your browser to authorize:\n";
        echo $authorizeUrl . "\n\n";
        echo "After authorizing, paste the verifier code here: ";

        // Read verifier from stdin
        $verifier = trim(fgets(STDIN));

        if (empty($verifier)) {
            fwrite(\STDERR, "No verifier provided. Aborting.\n");
            exit(1);
        }

        // Step 3: Exchange verifier for access token
        $accessToken = $flow->accessToken(
            $consumerKey,
            $consumerSecret,
            $requestToken['token'],
            $requestToken['tokenSecret'],
            $verifier
        );

        $userToken = $accessToken['token'];
        $userTokenSecret = $accessToken['tokenSecret'];

        // Cache the tokens
        file_put_contents($tokenCacheFile, json_encode([
            'token' => $userToken,
            'token_secret' => $userTokenSecret,
        ]));

        echo "Tokens cached. You won't need to authorize again next time.\n\n";
    } catch (\CleverCloud\Sdk\Exception\CleverCloudException $e) {
        fwrite(\STDERR, \sprintf("OAuth error: %s (%s)\n", $e->getMessage(), $e::class));
        fwrite(\STDERR, "Please check your CC_CONSUMER_KEY and CC_CONSUMER_SECRET are valid.\n");
        exit(1);
    }
}

// Build client with OAuth credentials
$client = new ClientBuilder()
    ->withCredentials(Credentials::oauth1(
        consumerKey: $consumerKey,
        consumerSecret: $consumerSecret,
        token: $userToken,
        tokenSecret: $userTokenSecret,
    ))
    ->build();

echo "Streaming logs for application: $appId\n";
if ($ownerId) {
    echo "(organisation: $ownerId)\n";
}
echo "Press Ctrl+C to stop.\n\n";

try {
    $deadline = time() + 60; // Stream for 60 seconds
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
