<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V2;

use CleverCloud\Sdk\Model\Enum\AvatarSource;
use CleverCloud\Sdk\Model\Enum\MemberRole;
use CleverCloud\Sdk\Resource\V2\OrganisationsResource;
use CleverCloud\Sdk\Tests\Unit\Fixture\ResourceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(OrganisationsResource::class)]
final class OrganisationsResourceTest extends TestCase
{
    public function testListReturnsTypedOrganisations(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            ['id' => 'orga_1', 'name' => 'Acme', 'canPay' => true, 'creation_date' => 1_700_000_000_000],
            ['id' => 'orga_2', 'name' => 'Beta'],
        ]);

        $orgs = $this->resource($response)->list();

        self::assertCount(2, $orgs);
        self::assertSame('orga_1', $orgs[0]->id);
        self::assertSame('Acme', $orgs[0]->name);
        self::assertTrue($orgs[0]->canPay);
        self::assertSame(1_700_000_000_000, $orgs[0]->creationDate);
        self::assertSame('orga_2', $orgs[1]->id);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/organisations', $response->getRequestUrl());
    }

    public function testGetSingleOrganisation(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'id' => 'orga_1',
            'name' => 'Acme',
            'description' => 'we do stuff',
            'billingEmail' => 'bills@acme.example',
            'canSEPA' => true,
            'isTrusted' => false,
        ]);

        $org = $this->resource($response)->get('orga_1');

        self::assertSame('orga_1', $org->id);
        self::assertSame('Acme', $org->name);
        self::assertSame('we do stuff', $org->description);
        self::assertSame('bills@acme.example', $org->billingEmail);
        self::assertTrue($org->canSepa);
        self::assertFalse($org->isTrusted);
        self::assertSame('https://api.clever-cloud.com/v2/organisations/orga_1', $response->getRequestUrl());
    }

    public function testCreateOrganisationPostsJsonBody(): void
    {
        $response = ResourceFactory::jsonResponse(201, [
            'id' => 'orga_new',
            'name' => 'New Co',
        ]);

        $org = $this->resource($response)->create(['name' => 'New Co', 'description' => 'desc']);

        self::assertSame('orga_new', $org->id);
        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame('{"name":"New Co","description":"desc"}', $response->getRequestOptions()['body']);
        self::assertContains('Content-Type: application/json', ResourceFactory::headers($response));
    }

    public function testAddMemberSendsJson(): void
    {
        $response = ResourceFactory::jsonResponse(204, []);

        $this->resource($response)->addMember('orga_1', 'alice@example.com', MemberRole::Developer, 'Engineer');

        self::assertSame('https://api.clever-cloud.com/v2/organisations/orga_1/members', $response->getRequestUrl());
        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame(
            '{"email":"alice@example.com","role":"DEVELOPER","job":"Engineer"}',
            $response->getRequestOptions()['body'],
        );
    }

    public function testRemoveMemberHitsDeleteEndpoint(): void
    {
        $response = ResourceFactory::jsonResponse(204, []);

        $this->resource($response)->removeMember('orga_1', 'user_42');

        self::assertSame('DELETE', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/members/user_42',
            $response->getRequestUrl(),
        );
    }

    public function testMembersReturnsTypedDtos(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            [
                'member' => ['id' => 'user_1', 'email' => 'a@example.com', 'name' => 'Alice Smith'],
                'role' => 'ADMIN',
                'job' => 'CTO',
            ],
            [
                'member' => ['id' => 'user_2', 'email' => 'b@example.com'],
                'role' => 'DEVELOPER',
            ],
        ]);

        $members = $this->resource($response)->members('orga_1');

        self::assertCount(2, $members);
        self::assertSame('user_1', $members[0]->member->id);
        self::assertSame('Alice Smith', $members[0]->member->name);
        self::assertSame(MemberRole::Admin, $members[0]->role);
        self::assertSame('CTO', $members[0]->job);
        self::assertSame(MemberRole::Developer, $members[1]->role);
        self::assertNull($members[1]->job);
    }

    public function testInvitationsReturnsTypedDtos(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            [
                'id' => 'inv_1',
                'organisationId' => 'orga_1',
                'inviteeEmail' => 'bob@example.com',
                'inviterUserId' => 'user_1',
                'role' => 'DEVELOPER',
                'status' => 'PENDING',
                'createdAt' => '2026-11-01T10:00:00Z',
                'expiresAt' => '2026-11-08T10:00:00Z',
                'acceptedAt' => null,
            ],
        ]);

        $invitations = $this->resource($response)->invitations('orga_1');

        self::assertCount(1, $invitations);
        self::assertSame('inv_1', $invitations[0]->id);
        self::assertSame('bob@example.com', $invitations[0]->inviteeEmail);
        self::assertSame(MemberRole::Developer, $invitations[0]->role);
        self::assertSame('PENDING', $invitations[0]->status);
        self::assertSame('2026-11-08T10:00:00Z', $invitations[0]->expiresAt);
        self::assertNull($invitations[0]->acceptedAt);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/members/invitations',
            $response->getRequestUrl(),
        );
    }

    public function testCancelInvitationHitsDelete(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['id' => 1, 'type' => 'success', 'message' => 'ok']);

        $this->resource($response)->cancelInvitation('orga_1', 'inv_1');

        self::assertSame('DELETE', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/members/invitations/inv_1',
            $response->getRequestUrl(),
        );
    }

    public function testSetAvatarPutsSourceAndReturnsUrl(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['url' => 'https://avatars.example/o.png']);

        $url = $this->resource($response)->setAvatar('orga_1', AvatarSource::Gravatar);

        self::assertSame('https://avatars.example/o.png', $url);
        self::assertSame('PUT', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/organisations/orga_1/avatar', $response->getRequestUrl());
        self::assertSame('{"source":"gravatar"}', $response->getRequestOptions()['body']);
    }

    public function testPaymentInfoReturnsTypedModel(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'fromOrga' => true,
            'errors' => [],
            'company' => 'Acme',
            'zipCode' => '44000',
        ]);

        $info = $this->resource($response)->paymentInfo('orga_1');

        self::assertTrue($info->fromOrga);
        self::assertSame([], $info->errors);
        self::assertSame('Acme', $info->company);
        self::assertSame('44000', $info->zipCode);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/organisations/orga_1/payment-info', $response->getRequestUrl());
    }

    public function testDrainPolicyReturnsRawPayload(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'enforcement' => ['LOG' => 'REQUIRED', 'ACCESSLOG' => 'NONE'],
            'drainTemplates' => [['id' => 'tpl_1', 'recipient' => ['type' => 'HTTP']]],
        ]);

        $policy = $this->resource($response)->drainPolicy('orga_1');

        self::assertSame(['LOG' => 'REQUIRED', 'ACCESSLOG' => 'NONE'], $policy['enforcement']);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/organisations/orga_1/drain-policy', $response->getRequestUrl());
    }

    public function testDeploymentsMapsEachApplicationToTypedDeployments(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'app_1' => [
                ['id' => 37, 'uuid' => 'deployment_1', 'state' => 'OK', 'action' => 'DEPLOY', 'instances' => 1, 'author' => ['id' => 'user_1']],
            ],
        ]);

        $deployments = $this->resource($response)->deployments('orga_1', 5);

        self::assertCount(1, $deployments['app_1']);
        self::assertSame('37', $deployments['app_1'][0]->id);
        self::assertSame('deployment_1', $deployments['app_1'][0]->uuid);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/deployments?limit=5',
            $response->getRequestUrl(),
        );
    }

    public function testDeploymentsReturnsEmptyArrayWhenRoleCannotSeeApplications(): void
    {
        $response = ResourceFactory::jsonResponse(200, []);

        self::assertSame([], $this->resource($response)->deployments('orga_1'));
        self::assertSame('https://api.clever-cloud.com/v2/organisations/orga_1/deployments', $response->getRequestUrl());
    }

    public function testInstancesReturnsMapKeyedByApplication(): void
    {
        $response = ResourceFactory::jsonResponse(200, [
            'app_1' => [['id' => 'inst_1', 'state' => 'UP']],
        ]);

        $instances = $this->resource($response)->instances('orga_1');

        self::assertSame('inst_1', $instances['app_1'][0]['id']);
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame('https://api.clever-cloud.com/v2/organisations/orga_1/instances', $response->getRequestUrl());
    }

    public function testConsumerSecretReturnsSecret(): void
    {
        $response = ResourceFactory::jsonResponse(200, ['secret' => 'cs_1']);

        self::assertSame('cs_1', $this->resource($response)->consumerSecret('orga_1', 'ck_1'));
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/consumers/ck_1/secret',
            $response->getRequestUrl(),
        );
    }

    private function resource(MockResponse $response): OrganisationsResource
    {
        return new OrganisationsResource(
            ResourceFactory::http(new MockHttpClient([$response])),
            ResourceFactory::mapper(),
        );
    }
}
