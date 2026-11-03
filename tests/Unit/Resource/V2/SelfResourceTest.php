<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V2;

use CleverCloud\Sdk\Model\Enum\AvatarSource;
use CleverCloud\Sdk\Resource\V2\SelfResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(SelfResource::class)]
final class SelfResourceTest extends TestCase
{
    public function testIdReturnsOwnerId(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['ownerId' => 'user_1']);

        self::assertSame('user_1', $this->resource($response)->id());
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/self/id', $response->getRequestUrl());
    }

    public function testSetAvatarPutsSourceAndReturnsUrl(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['url' => 'https://avatars.example/u.png']);

        $url = $this->resource($response)->setAvatar(AvatarSource::Github);

        self::assertSame('https://avatars.example/u.png', $url);
        self::assertSame('PUT', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/self/avatar', $response->getRequestUrl());
        self::assertSame('{"source":"github"}', $response->getRequestOptions()['body']);
    }

    public function testResendConfirmationEmailHitsGet(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['id' => 1, 'type' => 'success', 'message' => 'sent']);

        $this->resource($response)->resendConfirmationEmail();

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/self/confirmation_email', $response->getRequestUrl());
    }

    public function testValidateEmailSendsValidationKey(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['id' => 1, 'type' => 'success', 'message' => 'ok']);

        $this->resource($response)->validateEmail('key_123');

        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/self/validate_email?validationKey=key_123',
            $response->getRequestUrl(),
        );
    }

    public function testOrganisationsReturnsTypedOrganisations(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            ['id' => 'orga_1', 'name' => 'Acme'],
        ]);

        $orgs = $this->resource($response)->organisations();

        self::assertCount(1, $orgs);
        self::assertSame('orga_1', $orgs[0]->id);
        self::assertSame('Acme', $orgs[0]->name);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/self/organisations', $response->getRequestUrl());
    }

    public function testInstancesReturnsMapKeyedByApplication(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'app_1' => [['id' => 'inst_1', 'state' => 'UP']],
        ]);

        $instances = $this->resource($response)->instances();

        self::assertSame('inst_1', $instances['app_1'][0]['id']);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/self/instances', $response->getRequestUrl());
    }

    public function testPaymentInfoReturnsTypedModel(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'fromOrga' => false,
            'errors' => ['zipCode' => 'missing'],
            'name' => 'Alice',
            'city' => 'Nantes',
            'vatNumber' => null,
        ]);

        $info = $this->resource($response)->paymentInfo();

        self::assertFalse($info->fromOrga);
        self::assertSame(['zipCode' => 'missing'], $info->errors);
        self::assertSame('Alice', $info->name);
        self::assertSame('Nantes', $info->city);
        self::assertNull($info->vatNumber);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/self/payment-info', $response->getRequestUrl());
    }

    public function testTokensReturnsTypedAccessTokens(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            [
                'token' => 'tok_1',
                'consumer' => ['key' => 'ck_1', 'name' => 'CLI'],
                'rights' => ['almighty' => true, 'manage_ssh_keys' => false],
                'creationDate' => 1_700_000_000_000,
                'expirationDate' => null,
                'lastUtilisation' => 1_700_000_100_000,
            ],
        ]);

        $tokens = $this->resource($response)->tokens();

        self::assertCount(1, $tokens);
        self::assertSame('tok_1', $tokens[0]->token);
        self::assertSame('ck_1', $tokens[0]->consumer['key']);
        self::assertSame(['almighty' => true, 'manage_ssh_keys' => false], $tokens[0]->rights);
        self::assertSame(1_700_000_000_000, $tokens[0]->creationDate);
        self::assertNull($tokens[0]->expirationDate);
        self::assertSame(1_700_000_100_000, $tokens[0]->lastUtilisation);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/self/tokens', $response->getRequestUrl());
    }

    public function testCurrentTokenHitsCurrentEndpoint(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'token' => 'tok_current',
            'consumer' => ['key' => 'ck_1'],
            'rights' => ['almighty' => true],
        ]);

        $token = $this->resource($response)->currentToken();

        self::assertSame('tok_current', $token->token);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/self/tokens/current', $response->getRequestUrl());
    }

    public function testRevokeTokenHitsDelete(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['id' => 1, 'type' => 'success', 'message' => 'ok']);

        $this->resource($response)->revokeToken('tok_1');

        self::assertSame('DELETE', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/self/tokens/tok_1', $response->getRequestUrl());
    }

    public function testRevokeAllTokensHitsDeleteOnCollection(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['id' => 1, 'type' => 'success', 'message' => 'ok']);

        $this->resource($response)->revokeAllTokens();

        self::assertSame('DELETE', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/self/tokens', $response->getRequestUrl());
    }

    public function testCliTokensReturnsDecodedObject(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['token' => 'tk', 'secret' => 'ts']);

        $tokens = $this->resource($response)->cliTokens('cli_abc');

        self::assertSame(['token' => 'tk', 'secret' => 'ts'], $tokens);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/self/cli_tokens?cli_token=cli_abc',
            $response->getRequestUrl(),
        );
    }

    public function testCliTokensReturnsDecodedString(): void
    {
        $response = new MockResponse('"opaque"', [
            'http_code' => 200,
            'response_headers' => ['content-type' => 'application/json'],
        ]);

        self::assertSame('opaque', $this->resource($response)->cliTokens('cli_abc'));
    }

    public function testAddSshKeyPutsKeyAsBareJsonString(): void
    {
        $response = ResourceFactory::emptyResponse(200);

        $this->resource($response)->addSshKey('laptop', 'ssh-ed25519 AAAAC3+x/y me@host');

        self::assertSame('PUT', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/self/keys/laptop', $response->getRequestUrl());
        self::assertSame('"ssh-ed25519 AAAAC3+x/y me@host"', $response->getRequestOptions()['body']);
        self::assertContains('Content-Type: application/json', ResourceFactory::headers($response));
    }

    public function testCreateConsumerSendsRightsAsNameToBoolean(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'key' => 'ck_1',
            'name' => 'CI',
            'rights' => ['almighty' => false, 'manage_ssh_keys' => true],
        ]);

        $consumer = $this->resource($response)->createConsumer([
            'name' => 'CI',
            'description' => 'pipelines',
            'rights' => ['manage_ssh_keys' => true],
        ]);

        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/self/consumers', $response->getRequestUrl());
        self::assertSame(
            '{"name":"CI","description":"pipelines","rights":{"manage_ssh_keys":true}}',
            $response->getRequestOptions()['body'],
        );
        self::assertSame(['almighty' => false, 'manage_ssh_keys' => true], $consumer->rights);
    }

    public function testConsumerSecretReturnsSecret(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['secret' => 'cs_1']);

        self::assertSame('cs_1', $this->resource($response)->consumerSecret('ck_1'));
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/self/consumers/ck_1/secret', $response->getRequestUrl());
    }

    public function testConsumerTokensReturnsTypedAccessTokens(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            ['token' => 'tok_1', 'consumer' => ['key' => 'ck_1'], 'rights' => []],
        ]);

        $tokens = $this->resource($response)->consumerTokens('ck_1');

        self::assertCount(1, $tokens);
        self::assertSame('tok_1', $tokens[0]->token);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/self/consumers/ck_1/tokens', $response->getRequestUrl());
    }

    public function testMfaStatusReturnsTypedModel(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'kind' => 'TOTP',
            'configured' => true,
            'backupCodesState' => ['remaining' => 8, 'total' => 10, 'generatedAt' => '2026-01-01T00:00:00Z'],
        ]);

        $status = $this->resource($response)->mfaStatus('TOTP');

        self::assertSame('TOTP', $status->kind);
        self::assertTrue($status->configured);
        self::assertNotNull($status->backupCodesState);
        self::assertSame(8, $status->backupCodesState['remaining']);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/self/mfa/TOTP', $response->getRequestUrl());
    }

    public function testSetFavouriteMfaHitsPut(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['id' => 1, 'type' => 'success', 'message' => 'ok']);

        $this->resource($response)->setFavouriteMfa('TOTP');

        self::assertSame('PUT', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/self/mfa/TOTP', $response->getRequestUrl());
    }

    public function testRegenerateMfaBackupCodesPostsUnderTheMfaKind(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['backupCodes' => ['code-1', 'code-2']]);

        $codes = $this->resource($response)->regenerateMfaBackupCodes();

        self::assertSame(['code-1', 'code-2'], $codes);
        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/self/mfa/TOTP/backupcodes',
            $response->getRequestUrl(),
        );
    }

    public function testRegenerateMfaBackupCodesHonoursExplicitKind(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['backupCodes' => []]);

        $codes = $this->resource($response)->regenerateMfaBackupCodes('totp');

        self::assertSame([], $codes);
        self::assertSame(
            'https://api.clever-cloud.com/v2/self/mfa/totp/backupcodes',
            $response->getRequestUrl(),
        );
    }

    private function resource(MockResponse $response): SelfResource
    {
        return new SelfResource(
            ResourceFactory::http(new MockHttpClient([$response])),
            ResourceFactory::mapper(),
        );
    }
}
