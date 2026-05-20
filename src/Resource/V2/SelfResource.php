<?php

namespace CleverCloud\Sdk\Resource\V2;

use CleverCloud\Sdk\Model\EmailAddress;
use CleverCloud\Sdk\Model\OAuthConsumer;
use CleverCloud\Sdk\Model\SshKey;
use CleverCloud\Sdk\Model\User;
use CleverCloud\Sdk\Resource\AbstractV2Resource;

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

    public function addSshKey(string $name, string $publicKey): void
    {
        $this->httpPut('/self/keys/'.rawurlencode($name), ['json' => ['key' => $publicKey]]);
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
     * @param array{name: string, url: string, baseUrl?: string, description?: string, picture?: string, rights?: list<string>} $data
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
     * Returns the regenerated MFA backup codes.
     *
     * @return list<string>
     */
    public function regenerateMfaBackupCodes(): array
    {
        /** @var list<string> $payload */
        $payload = $this->httpPost('/self/mfa/backupcodes');

        return $payload;
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
}
