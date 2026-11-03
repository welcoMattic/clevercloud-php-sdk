<?php

namespace CleverCloud\Sdk\Resource\V2;

use CleverCloud\Sdk\Resource\AbstractV2Resource;
use CleverCloud\Sdk\Streaming\EventStream;

/**
 * Platform events (deployments, application and add-on lifecycle) of the
 * current user's personal space and of every organisation they belong to,
 * streamed over Server-Sent Events from `/v2/events`.
 *
 * The `/v2/events/event-socket` WebSocket variant is not supported.
 */
final readonly class EventsResource extends AbstractV2Resource
{
    /**
     * Opens the SSE stream of platform events. The API documents no schema
     * for their payload, so each one is yielded as a
     * {@see \CleverCloud\Sdk\Model\StreamEvent} holding the decoded JSON. The
     * stream never ends on its own: break out of the loop when you are done.
     *
     * @param list<string> $types event types to receive, for example `['DEPLOYMENT_SUCCESS', 'APPLICATION_CREATION']`; every type when empty
     */
    public function stream(array $types = []): EventStream
    {
        $query = [] !== $types ? ['types' => implode(',', $types)] : [];

        return new EventStream($this->httpEventStream('/events', ['query' => $query]));
    }
}
