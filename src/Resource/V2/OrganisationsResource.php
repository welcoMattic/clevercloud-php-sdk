<?php

namespace CleverCloud\Sdk\Resource\V2;

use CleverCloud\Sdk\Model\Deployment;
use CleverCloud\Sdk\Model\Enum\AvatarSource;
use CleverCloud\Sdk\Model\Enum\MemberRole;
use CleverCloud\Sdk\Model\Invitation;
use CleverCloud\Sdk\Model\Member;
use CleverCloud\Sdk\Model\Namespace_;
use CleverCloud\Sdk\Model\OAuthConsumer;
use CleverCloud\Sdk\Model\Organisation;
use CleverCloud\Sdk\Model\PaymentInfo;
use CleverCloud\Sdk\Resource\AbstractV2Resource;

final readonly class OrganisationsResource extends AbstractV2Resource
{
    /**
     * @return list<Organisation>
     */
    public function list(): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet('/organisations');

        return $this->mapCollection(Organisation::class, $payload);
    }

    public function get(string $id): Organisation
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet('/organisations/'.rawurlencode($id));

        return $this->mapTo(Organisation::class, $payload);
    }

    /**
     * @param array{name?: string, description?: string, address?: string, city?: string,
     *              zipcode?: string, country?: string, company?: string, vat?: string,
     *              avatar?: string} $data
     */
    public function create(array $data): Organisation
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost('/organisations', ['json' => $data]);

        return $this->mapTo(Organisation::class, $payload);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): Organisation
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPut('/organisations/'.rawurlencode($id), ['json' => $data]);

        return $this->mapTo(Organisation::class, $payload);
    }

    public function delete(string $id): void
    {
        $this->httpDelete('/organisations/'.rawurlencode($id));
    }

    /**
     * @return list<Member>
     */
    public function members(string $organisationId): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet('/organisations/'.rawurlencode($organisationId).'/members');

        return $this->mapCollection(Member::class, $payload);
    }

    public function addMember(string $organisationId, string $userEmail, MemberRole $role, ?string $job = null): void
    {
        $body = ['email' => $userEmail, 'role' => $role->value];
        if (null !== $job) {
            $body['job'] = $job;
        }
        $this->httpPost('/organisations/'.rawurlencode($organisationId).'/members', ['json' => $body]);
    }

    public function updateMember(string $organisationId, string $userId, MemberRole $role, ?string $job = null): void
    {
        $body = ['role' => $role->value];
        if (null !== $job) {
            $body['job'] = $job;
        }
        $this->httpPut(
            '/organisations/'.rawurlencode($organisationId).'/members/'.rawurlencode($userId),
            ['json' => $body],
        );
    }

    public function removeMember(string $organisationId, string $userId): void
    {
        $this->httpDelete(
            '/organisations/'.rawurlencode($organisationId).'/members/'.rawurlencode($userId),
        );
    }

    // ------------------------------------------------------------------
    //  OAuth consumers registered under this organisation
    // ------------------------------------------------------------------

    /**
     * @return list<OAuthConsumer>
     */
    public function consumers(string $organisationId): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet('/organisations/'.rawurlencode($organisationId).'/consumers');

        return $this->mapCollection(OAuthConsumer::class, $payload);
    }

    public function getConsumer(string $organisationId, string $consumerKey): OAuthConsumer
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet(
            '/organisations/'.rawurlencode($organisationId).'/consumers/'.rawurlencode($consumerKey),
        );

        return $this->mapTo(OAuthConsumer::class, $payload);
    }

    /**
     * @param array{name: string, description: string, rights: array<string, bool>, url?: string, baseUrl?: string, picture?: string} $data
     */
    public function createConsumer(string $organisationId, array $data): OAuthConsumer
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPost(
            '/organisations/'.rawurlencode($organisationId).'/consumers',
            ['json' => $data],
        );

        return $this->mapTo(OAuthConsumer::class, $payload);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateConsumer(string $organisationId, string $consumerKey, array $data): OAuthConsumer
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpPut(
            '/organisations/'.rawurlencode($organisationId).'/consumers/'.rawurlencode($consumerKey),
            ['json' => $data],
        );

        return $this->mapTo(OAuthConsumer::class, $payload);
    }

    public function deleteConsumer(string $organisationId, string $consumerKey): void
    {
        $this->httpDelete(
            '/organisations/'.rawurlencode($organisationId).'/consumers/'.rawurlencode($consumerKey),
        );
    }

    // ------------------------------------------------------------------
    //  Namespaces
    // ------------------------------------------------------------------

    /**
     * @return list<Namespace_>
     */
    public function namespaces(string $organisationId): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet('/organisations/'.rawurlencode($organisationId).'/namespaces');

        return $this->mapCollection(Namespace_::class, $payload);
    }

    // ------------------------------------------------------------------
    //  Member invitations
    // ------------------------------------------------------------------

    /**
     * Lists the pending invitations to join an organisation.
     *
     * @return list<Invitation>
     */
    public function invitations(string $organisationId): array
    {
        /** @var list<array<string, mixed>> $payload */
        $payload = $this->httpGet('/organisations/'.rawurlencode($organisationId).'/members/invitations');

        return $this->mapCollection(Invitation::class, $payload);
    }

    /**
     * Cancels a pending invitation to join an organisation.
     */
    public function cancelInvitation(string $organisationId, string $invitationId): void
    {
        $this->httpDelete(
            '/organisations/'.rawurlencode($organisationId).'/members/invitations/'.rawurlencode($invitationId),
        );
    }

    // ------------------------------------------------------------------
    //  Avatar, billing details, drain policy
    // ------------------------------------------------------------------

    /**
     * Replaces the organisation's avatar with one taken from an external
     * source. The API derives the avatar URL from the source and returns it.
     */
    public function setAvatar(string $organisationId, AvatarSource $source): string
    {
        /** @var array{url?: string} $payload */
        $payload = $this->httpPut(
            '/organisations/'.rawurlencode($organisationId).'/avatar',
            ['json' => ['source' => $source->value]],
        );

        return (string) ($payload['url'] ?? '');
    }

    /**
     * Returns the organisation's billing details.
     */
    public function paymentInfo(string $organisationId): PaymentInfo
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet('/organisations/'.rawurlencode($organisationId).'/payment-info');

        return $this->mapTo(PaymentInfo::class, $payload);
    }

    /**
     * Returns the organisation's drain policy: `enforcement` maps each drain
     * kind (`LOG`, `ACCESSLOG`, `AUDITLOG`) to `NONE` or `REQUIRED`, and
     * `drainTemplates` lists the templates it applies (`{id, recipient}`,
     * with masked credentials).
     *
     * @return array<string, mixed>
     */
    public function drainPolicy(string $organisationId): array
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->httpGet('/organisations/'.rawurlencode($organisationId).'/drain-policy');

        return $payload;
    }

    // ------------------------------------------------------------------
    //  Organisation-wide deployments and instances
    // ------------------------------------------------------------------

    /**
     * Lists the recent deployments of the organisation's applications, keyed
     * by application id (`app_...`). Returns an empty array when the caller's
     * role cannot see the organisation's applications.
     *
     * @return array<string, list<Deployment>>
     */
    public function deployments(string $organisationId, ?int $limit = null): array
    {
        $query = null === $limit ? [] : ['limit' => $limit];

        /** @var array<string, list<array<string, mixed>>> $payload */
        $payload = $this->httpGet(
            '/organisations/'.rawurlencode($organisationId).'/deployments',
            ['query' => $query],
        );

        $deployments = [];
        foreach ($payload as $applicationId => $items) {
            $deployments[$applicationId] = $this->mapCollection(Deployment::class, $items);
        }

        return $deployments;
    }

    /**
     * Lists the running instances of the organisation's applications, keyed
     * by application id (`app_...`). Returns an empty array when the caller's
     * role cannot see the organisation's applications.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public function instances(string $organisationId): array
    {
        /** @var array<string, list<array<string, mixed>>> $payload */
        $payload = $this->httpGet('/organisations/'.rawurlencode($organisationId).'/instances');

        return $payload;
    }

    // ------------------------------------------------------------------
    //  OAuth consumer secret
    // ------------------------------------------------------------------

    /**
     * Returns the secret of one of the organisation's OAuth consumers.
     */
    public function consumerSecret(string $organisationId, string $consumerKey): string
    {
        /** @var array{secret?: string} $payload */
        $payload = $this->httpGet(
            '/organisations/'.rawurlencode($organisationId).'/consumers/'.rawurlencode($consumerKey).'/secret',
        );

        return (string) ($payload['secret'] ?? '');
    }
}
