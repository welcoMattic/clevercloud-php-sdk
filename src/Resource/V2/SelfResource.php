<?php

namespace CleverCloud\Sdk\Resource\V2;

use CleverCloud\Sdk\Exception\JsonException;
use CleverCloud\Sdk\Model\EmailAddress;
use CleverCloud\Sdk\Model\Enum\AvatarSource;
use CleverCloud\Sdk\Model\MfaStatus;
use CleverCloud\Sdk\Model\OAuthAccessToken;
use CleverCloud\Sdk\Model\OAuthConsumer;
use CleverCloud\Sdk\Model\Organisation;
use CleverCloud\Sdk\Model\PaymentInfo;
use CleverCloud\Sdk\Model\SshKey;
use CleverCloud\Sdk\Model\User;
use CleverCloud\Sdk\Resource\AbstractV2Resource;

use const JSON_THROW_ON_ERROR;

final readonly class SelfResource extends AbstractV2Resource
{
    public function get(): User
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet('/self');

        return $this->mapTo(User::class, $payload);
    }

    /**
     * @param array<string, mixed> $data partial user payload (firstname, lastname, address, …)
     */
    public function update(array $data): User
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPut('/self', ['json' => $data]);

        return $this->mapTo(User::class, $payload);
    }

    // ------------------------------------------------------------------
    //  SSH keys
    // ------------------------------------------------------------------

    /**
     * @return list<SshKey>
     */
    public function sshKeys(): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet('/self/keys');

        return $this->mapCollection(SshKey::class, $payload);
    }

    /**
     * The body is the public key as a bare JSON string
     * (`"ssh-ed25519 AAAA... comment"`). The spec declares `text/plain`; this
     * sends `application/json` with the same body, which is what clever-tools
     * sends in production.
     */
    public function addSshKey(string $name, string $publicKey): void
    {
        $this->httpPut('/self/keys/'.rawurlencode($name), ['json' => $publicKey]);
    }

    public function removeSshKey(string $name): void
    {
        $this->httpDelete('/self/keys/'.rawurlencode($name));
    }

    // ------------------------------------------------------------------
    //  Secondary email addresses
    // ------------------------------------------------------------------

    /**
     * @return list<EmailAddress>
     */
    public function emailAddresses(): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet('/self/emails');

        return $this->mapCollection(EmailAddress::class, $payload);
    }

    public function addEmailAddress(string $email, bool $makePrimary = false): void
    {
        $this->httpPut(
            '/self/emails/'.rawurlencode($email),
            ['json' => ['make_primary' => $makePrimary]],
        );
    }

    public function removeEmailAddress(string $email): void
    {
        $this->httpDelete('/self/emails/'.rawurlencode($email));
    }

    // ------------------------------------------------------------------
    //  OAuth consumers registered to this user
    // ------------------------------------------------------------------

    /**
     * @return list<OAuthConsumer>
     */
    public function consumers(): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet('/self/consumers');

        return $this->mapCollection(OAuthConsumer::class, $payload);
    }

    public function getConsumer(string $consumerKey): OAuthConsumer
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet('/self/consumers/'.rawurlencode($consumerKey));

        return $this->mapTo(OAuthConsumer::class, $payload);
    }

    /**
     * @param array{name: string, description: string, rights: array<string, bool>, url?: string, baseUrl?: string, picture?: string} $data
     */
    public function createConsumer(array $data): OAuthConsumer
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost('/self/consumers', ['json' => $data]);

        return $this->mapTo(OAuthConsumer::class, $payload);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateConsumer(string $consumerKey, array $data): OAuthConsumer
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPut(
            '/self/consumers/'.rawurlencode($consumerKey),
            ['json' => $data],
        );

        return $this->mapTo(OAuthConsumer::class, $payload);
    }

    public function deleteConsumer(string $consumerKey): void
    {
        $this->httpDelete('/self/consumers/'.rawurlencode($consumerKey));
    }

    // ------------------------------------------------------------------
    //  MFA (2FA)
    // ------------------------------------------------------------------

    /**
     * @return array<string, mixed> { kind, otpUrl?, qrCodeUrl?, backupCodes? }
     */
    public function startMfa(string $kind = 'TOTP'): array
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost(
            '/self/mfa/'.rawurlencode($kind),
            ['json' => ['kind' => $kind]],
        );

        return $payload;
    }

    public function confirmMfa(string $kind, string $code): void
    {
        $this->httpPost(
            '/self/mfa/'.rawurlencode($kind).'/confirmation',
            ['json' => ['code' => $code]],
        );
    }

    public function disableMfa(string $kind): void
    {
        $this->httpDelete('/self/mfa/'.rawurlencode($kind));
    }

    /**
     * Replaces the recovery codes of the validated MFA of the given kind and
     * returns the new set: the previous codes stop working, and this response
     * is the only one that shows the new ones.
     *
     * @return list<string>
     */
    public function regenerateMfaBackupCodes(string $kind = 'TOTP'): array
    {
        /** @var array{backupCodes?: list<string>} $payload */
        $payload = $this->httpPost('/self/mfa/'.rawurlencode($kind).'/backupcodes');

        return array_values($payload['backupCodes'] ?? []);
    }

    // ------------------------------------------------------------------
    //  Password
    // ------------------------------------------------------------------

    public function changePassword(string $oldPassword, string $newPassword): void
    {
        $this->httpPut('/self/change_password', [
            'json' => [
                'oldPassword' => $oldPassword,
                'newPassword' => $newPassword,
            ],
        ]);
    }

    // ------------------------------------------------------------------
    //  Account
    // ------------------------------------------------------------------

    /**
     * Returns the owner identifier (`user_...`) of the authenticated user.
     */
    public function id(): string
    {
        /** @var array{ownerId?: string} $payload */
        $payload = $this->httpGet('/self/id');

        return (string) ($payload['ownerId'] ?? '');
    }

    /**
     * Sets the user's avatar from an external source. The API derives the
     * avatar URL from the source and returns it.
     */
    public function setAvatar(AvatarSource $source): string
    {
        /** @var array{url?: string} $payload */
        $payload = $this->httpPut('/self/avatar', ['json' => ['source' => $source->value]]);

        return (string) ($payload['url'] ?? '');
    }

    /**
     * Sends the account confirmation email again.
     */
    public function resendConfirmationEmail(): void
    {
        $this->httpGet('/self/confirmation_email');
    }

    /**
     * Confirms an email address with the validation key received by email.
     */
    public function validateEmail(string $validationKey): void
    {
        $this->httpGet('/self/validate_email', ['query' => ['validationKey' => $validationKey]]);
    }

    /**
     * Lists the organisations the current user belongs to.
     *
     * @return list<Organisation>
     */
    public function organisations(): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet('/self/organisations');

        return $this->mapCollection(Organisation::class, $payload);
    }

    /**
     * Lists the running instances of all the current user's applications,
     * keyed by application id (`app_...`).
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public function instances(): array
    {
        /** @var array<string, list<array<string, mixed>>> $payload */
        $payload = $this->httpGet('/self/instances');

        return $payload;
    }

    /**
     * Returns the current user's billing details.
     */
    public function paymentInfo(): PaymentInfo
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet('/self/payment-info');

        return $this->mapTo(PaymentInfo::class, $payload);
    }

    // ------------------------------------------------------------------
    //  OAuth access tokens
    // ------------------------------------------------------------------

    /**
     * Lists the current user's OAuth access tokens.
     *
     * @return list<OAuthAccessToken>
     */
    public function tokens(): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet('/self/tokens');

        return $this->mapCollection(OAuthAccessToken::class, $payload);
    }

    /**
     * Returns the access token used to sign this request.
     */
    public function currentToken(): OAuthAccessToken
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet('/self/tokens/current');

        return $this->mapTo(OAuthAccessToken::class, $payload);
    }

    /**
     * Revokes one of the current user's access tokens.
     */
    public function revokeToken(string $token): void
    {
        $this->httpDelete('/self/tokens/'.rawurlencode($token));
    }

    /**
     * Revokes all the current user's access tokens, including the one used to
     * sign this request.
     */
    public function revokeAllTokens(): void
    {
        $this->httpDelete('/self/tokens');
    }

    /**
     * Collects the tokens of a pending command-line login, identified by the
     * `cli_token` the CLI generated. The endpoint does not require
     * authentication.
     *
     * The spec declares the response as a JSON string while the Clever Cloud
     * CLI reads a `{token, secret}` object, so the decoded body is returned
     * as is: a string or an array.
     *
     * @return string|array<string, mixed>
     */
    public function cliTokens(string $cliToken): string|array
    {
        $response = $this->httpStream('GET', '/self/cli_tokens', ['query' => ['cli_token' => $cliToken]]);

        try {
            $decoded = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new JsonException('Unable to decode JSON: '.$e->getMessage(), 0, $e);
        }

        if (\is_string($decoded)) {
            return $decoded;
        }
        if (!\is_array($decoded)) {
            throw new JsonException('Expected a JSON string or object, got '.get_debug_type($decoded));
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    // ------------------------------------------------------------------
    //  OAuth consumers: secret and tokens
    // ------------------------------------------------------------------

    /**
     * Returns the secret of one of the current user's OAuth consumers.
     */
    public function consumerSecret(string $consumerKey): string
    {
        /** @var array{secret?: string} $payload */
        $payload = $this->httpGet('/self/consumers/'.rawurlencode($consumerKey).'/secret');

        return (string) ($payload['secret'] ?? '');
    }

    /**
     * Lists the access tokens issued through one of the current user's OAuth
     * consumers.
     *
     * @return list<OAuthAccessToken>
     */
    public function consumerTokens(string $consumerKey): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet('/self/consumers/'.rawurlencode($consumerKey).'/tokens');

        return $this->mapCollection(OAuthAccessToken::class, $payload);
    }

    // ------------------------------------------------------------------
    //  MFA status and preference
    // ------------------------------------------------------------------

    /**
     * Returns whether an MFA kind is configured for the current user.
     */
    public function mfaStatus(string $kind = 'TOTP'): MfaStatus
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet('/self/mfa/'.rawurlencode($kind));

        return $this->mapTo(MfaStatus::class, $payload);
    }

    /**
     * Makes an MFA kind the current user's preferred one.
     */
    public function setFavouriteMfa(string $kind): void
    {
        $this->httpPut('/self/mfa/'.rawurlencode($kind));
    }
}
