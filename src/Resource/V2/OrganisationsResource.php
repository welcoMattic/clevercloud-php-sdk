<?php

namespace CleverCloud\Sdk\Resource\V2;

use CleverCloud\Sdk\Model\Enum\MemberRole;
use CleverCloud\Sdk\Model\Member;
use CleverCloud\Sdk\Model\Organisation;
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
}
