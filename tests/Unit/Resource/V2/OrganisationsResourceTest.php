<?php

namespace CleverCloud\Sdk\Tests\Unit\Resource\V2;

use AutoMapper\AutoMapper;
use CleverCloud\Sdk\Auth\Credentials;
use CleverCloud\Sdk\Auth\OAuth1Signer;
use CleverCloud\Sdk\Configuration;
use CleverCloud\Sdk\Http\HttpClient;
use CleverCloud\Sdk\Http\JsonCodec;
use CleverCloud\Sdk\Http\RetryPolicy;
use CleverCloud\Sdk\Http\UriBuilder;
use CleverCloud\Sdk\Model\Enum\MemberRole;
use CleverCloud\Sdk\Resource\V2\OrganisationsResource;
use CleverCloud\Sdk\Tests\Unit\Auth\StaticNonceGenerator;

use const JSON_THROW_ON_ERROR;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Clock\MockClock;

#[CoversClass(OrganisationsResource::class)]
final class OrganisationsResourceTest extends TestCase
{
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
    }

    public function testListReturnsTypedOrganisations(): void
    {
        $psr18 = new RecordingClient($this->jsonResponse(200, [
            ['id' => 'orga_1', 'name' => 'Acme', 'canPay' => true, 'creation_date' => 1_700_000_000_000],
            ['id' => 'orga_2', 'name' => 'Beta'],
        ]));

        $orgs = $this->resource($psr18)->list();

        self::assertCount(2, $orgs);
        self::assertSame('orga_1', $orgs[0]->id);
        self::assertSame('Acme', $orgs[0]->name);
        self::assertTrue($orgs[0]->canPay);
        self::assertSame(1_700_000_000_000, $orgs[0]->creationDate);
        self::assertSame('orga_2', $orgs[1]->id);

        self::assertNotNull($psr18->lastRequest);
        self::assertSame('https://api.clever-cloud.com/v2/organisations', (string) $psr18->lastRequest->getUri());
    }

    public function testGetSingleOrganisation(): void
    {
        $psr18 = new RecordingClient($this->jsonResponse(200, [
            'id' => 'orga_1',
            'name' => 'Acme',
            'description' => 'we do stuff',
            'billingEmail' => 'bills@acme.example',
            'canSEPA' => true,
            'isTrusted' => false,
        ]));

        $org = $this->resource($psr18)->get('orga_1');

        self::assertSame('orga_1', $org->id);
        self::assertSame('Acme', $org->name);
        self::assertSame('we do stuff', $org->description);
        self::assertSame('bills@acme.example', $org->billingEmail);
        self::assertTrue($org->canSepa);
        self::assertFalse($org->isTrusted);
        self::assertNotNull($psr18->lastRequest);
        self::assertSame('https://api.clever-cloud.com/v2/organisations/orga_1', (string) $psr18->lastRequest->getUri());
    }

    public function testCreateOrganisationPostsJsonBody(): void
    {
        $psr18 = new RecordingClient($this->jsonResponse(201, [
            'id' => 'orga_new',
            'name' => 'New Co',
        ]));

        $org = $this->resource($psr18)->create(['name' => 'New Co', 'description' => 'desc']);

        self::assertSame('orga_new', $org->id);
        self::assertNotNull($psr18->lastRequest);
        self::assertSame('POST', $psr18->lastRequest->getMethod());
        self::assertSame('application/json', $psr18->lastRequest->getHeaderLine('Content-Type'));
        self::assertSame('{"name":"New Co","description":"desc"}', (string) $psr18->lastRequest->getBody());
    }

    public function testAddMemberSendsJson(): void
    {
        $psr18 = new RecordingClient($this->jsonResponse(204, []));

        $this->resource($psr18)->addMember('orga_1', 'alice@example.com', MemberRole::Developer, 'Engineer');

        self::assertNotNull($psr18->lastRequest);
        self::assertSame('https://api.clever-cloud.com/v2/organisations/orga_1/members', (string) $psr18->lastRequest->getUri());
        self::assertSame('POST', $psr18->lastRequest->getMethod());
        self::assertSame(
            '{"email":"alice@example.com","role":"DEVELOPER","job":"Engineer"}',
            (string) $psr18->lastRequest->getBody(),
        );
    }

    public function testRemoveMemberHitsDeleteEndpoint(): void
    {
        $psr18 = new RecordingClient($this->jsonResponse(204, []));

        $this->resource($psr18)->removeMember('orga_1', 'user_42');

        self::assertNotNull($psr18->lastRequest);
        self::assertSame('DELETE', $psr18->lastRequest->getMethod());
        self::assertSame(
            'https://api.clever-cloud.com/v2/organisations/orga_1/members/user_42',
            (string) $psr18->lastRequest->getUri(),
        );
    }

    public function testMembersReturnsTypedDtos(): void
    {
        $psr18 = new RecordingClient($this->jsonResponse(200, [
            [
                'member' => ['id' => 'user_1', 'email' => 'a@example.com', 'firstname' => 'Alice'],
                'role' => 'ADMIN',
                'job' => 'CTO',
            ],
            [
                'member' => ['id' => 'user_2', 'email' => 'b@example.com'],
                'role' => 'DEVELOPER',
            ],
        ]));

        $members = $this->resource($psr18)->members('orga_1');

        self::assertCount(2, $members);
        self::assertSame('user_1', $members[0]->member->id);
        self::assertSame('Alice', $members[0]->member->firstname);
        self::assertSame(MemberRole::Admin, $members[0]->role);
        self::assertSame('CTO', $members[0]->job);
        self::assertSame(MemberRole::Developer, $members[1]->role);
        self::assertNull($members[1]->job);
    }

    /**
     * @param array<int|string, mixed> $payload
     */
    private function jsonResponse(int $status, array $payload): ResponseInterface
    {
        return $this->factory
            ->createResponse($status)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->factory->createStream(json_encode($payload, JSON_THROW_ON_ERROR)));
    }

    private function resource(RecordingClient $psr18): OrganisationsResource
    {
        $configuration = new Configuration();
        $http = new HttpClient(
            psr18: $psr18,
            requestFactory: $this->factory,
            streamFactory: $this->factory,
            uriBuilder: new UriBuilder($configuration, $this->factory),
            signer: new OAuth1Signer(new MockClock('@1700000000'), new StaticNonceGenerator('nonce')),
            credentials: new Credentials('ck', 'cs', 'tk', 'ts'),
            configuration: $configuration,
            jsonCodec: new JsonCodec(),
            retryPolicy: new RetryPolicy(maxAttempts: 1, jitterMs: 0),
        );

        return new OrganisationsResource($http, AutoMapper::create());
    }
}

final class RecordingClient implements ClientInterface
{
    public ?RequestInterface $lastRequest = null;

    public function __construct(private readonly ResponseInterface $response)
    {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->lastRequest = $request;

        return $this->response;
    }
}
