<?php

namespace CleverCloud\Sdk\Tests\Integration;

use CleverCloud\Sdk\Auth\Credentials;
use CleverCloud\Sdk\Client;
use CleverCloud\Sdk\ClientBuilder;
use CleverCloud\Sdk\Configuration;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Base class for tests that hit the real Clever Cloud API.
 *
 * Tests are gated on credentials being present in the environment, and skip
 * cleanly otherwise so contributors running `composer test` without secrets
 * configured don't see false failures.
 *
 * Supported credential layouts (Bearer is preferred):
 *
 *   CC_API_TOKEN=cc_...               (single Bearer token, routed via api-bridge)
 *
 *   or:
 *
 *   CC_CONSUMER_KEY=...
 *   CC_CONSUMER_SECRET=...
 *   CC_TOKEN=...
 *   CC_TOKEN_SECRET=...               (3-legged OAuth 1.0a)
 *
 * Optional, used by tests that need a target resource:
 *
 *   CC_ORG_ID=orga_xxx                (else: first organisation listed by /self)
 *   CC_APP_ID=app_xxx                 (else: first application of the chosen org)
 */
#[Group('integration')]
abstract class IntegrationTestCase extends TestCase
{
    protected Client $client;

    protected function setUp(): void
    {
        $credentials = self::credentialsFromEnv();
        if (null === $credentials) {
            self::markTestSkipped(
                'Integration tests skipped — set CC_API_TOKEN or the four '
                .'CC_CONSUMER_KEY/CC_CONSUMER_SECRET/CC_TOKEN/CC_TOKEN_SECRET '
                .'env vars to run the live test suite.',
            );
        }

        $this->client = new ClientBuilder()
            ->withCredentials($credentials)
            ->withConfiguration(new Configuration(
                userAgent: 'clevercloud-php-sdk/integration-tests',
                timeoutSeconds: 20,
            ))
            ->build();
    }

    private static function credentialsFromEnv(): ?Credentials
    {
        $apiToken = self::env('CC_API_TOKEN');
        if (null !== $apiToken) {
            return Credentials::apiToken($apiToken);
        }

        $consumerKey = self::env('CC_CONSUMER_KEY');
        $consumerSecret = self::env('CC_CONSUMER_SECRET');
        $token = self::env('CC_TOKEN');
        $tokenSecret = self::env('CC_TOKEN_SECRET');

        if (
            null !== $consumerKey
            && null !== $consumerSecret
            && null !== $token
            && null !== $tokenSecret
        ) {
            return Credentials::oauth1($consumerKey, $consumerSecret, $token, $tokenSecret);
        }

        return null;
    }

    /**
     * Returns the org id tests should target — explicit via CC_ORG_ID, else
     * the first organisation listed for the authenticated user. Skips the
     * calling test if no organisation is reachable.
     */
    protected function targetOrganisationId(): string
    {
        $explicit = self::env('CC_ORG_ID');
        if (null !== $explicit) {
            return $explicit;
        }

        $organisations = $this->client->organisations->list();
        if ([] === $organisations) {
            self::markTestSkipped('No organisation reachable for the authenticated account.');
        }

        return $organisations[0]->id;
    }

    /**
     * Returns the application id tests should target — explicit via CC_APP_ID,
     * else the first app of {@see targetOrganisationId()}. Skips the calling
     * test if the org has no applications.
     */
    protected function targetApplicationId(?string $organisationId = null): string
    {
        $explicit = self::env('CC_APP_ID');
        if (null !== $explicit) {
            return $explicit;
        }

        $organisationId ??= $this->targetOrganisationId();
        $applications = $this->client->applications->list($organisationId);
        if ([] === $applications) {
            self::markTestSkipped("No application reachable under organisation {$organisationId}.");
        }

        return $applications[0]->id;
    }

    private static function env(string $name): ?string
    {
        $value = getenv($name);
        if (false === $value || '' === $value) {
            return null;
        }

        return $value;
    }
}
