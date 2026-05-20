<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V2;

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
                'member' => ['id' => 'user_1', 'email' => 'a@example.com', 'firstname' => 'Alice'],
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
        self::assertSame('Alice', $members[0]->member->firstname);
        self::assertSame(MemberRole::Admin, $members[0]->role);
        self::assertSame('CTO', $members[0]->job);
        self::assertSame(MemberRole::Developer, $members[1]->role);
        self::assertNull($members[1]->job);
    }

    private function resource(MockResponse $response): OrganisationsResource
    {
        return new OrganisationsResource(
            ResourceFactory::http(new MockHttpClient([$response])),
            ResourceFactory::mapper(),
        );
    }
}
