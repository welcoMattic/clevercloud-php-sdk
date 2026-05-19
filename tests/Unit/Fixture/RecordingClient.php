<?php

namespace CleverCloud\Sdk\Tests\Unit\Fixture;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * Records the last request seen by a PSR-18 sender and replays a queue of
 * pre-built responses. Used by every resource-level unit test that needs to
 * assert on the request shape and inject a fixed response.
 */
final class RecordingClient implements ClientInterface
{
    public ?RequestInterface $lastRequest = null;
    /** @var list<RequestInterface> */
    public array $allRequests = [];

    /** @var list<ResponseInterface> */
    private array $queue;

    /**
     * @param list<ResponseInterface>|ResponseInterface $responses
     */
    public function __construct(array|ResponseInterface $responses)
    {
        $this->queue = \is_array($responses) ? array_values($responses) : [$responses];
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->lastRequest = $request;
        $this->allRequests[] = $request;

        if ([] === $this->queue) {
            throw new RuntimeException('RecordingClient: no more queued responses');
        }

        return array_shift($this->queue);
    }
}
