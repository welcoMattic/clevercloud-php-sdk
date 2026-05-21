<?php

namespace App\Controller;

use CleverCloud\Sdk\Client;
use CleverCloud\Sdk\Exception\CleverCloudException;
use CleverCloud\Sdk\Exception\NotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

use const JSON_THROW_ON_ERROR;

/**
 * Live tail of application logs.
 *
 * `/applications/{id}/logs`            → HTML page subscribing via EventSource
 * `/applications/{id}/logs/stream`     → server-sent events stream re-emitting
 *                                         each {@see LogEntry} as a JSON data:
 *                                         frame to the browser.
 */
final class LogsController extends AbstractController
{
    public function __construct(private readonly Client $cc)
    {
    }

    #[Route('/applications/{id}/logs', name: 'application_logs', methods: ['GET'])]
    public function page(Request $request, string $id): Response
    {
        $owner = $this->normaliseOwner($request->query->get('owner'));

        try {
            $application = $this->cc->applications->get($id, $owner);
        } catch (CleverCloudException $e) {
            return $this->render('dashboard/error.html.twig', ['exception' => $e]);
        }

        return $this->render('application/logs.html.twig', [
            'application' => $application,
            'owner' => $owner,
            'streamUrl' => $this->generateUrl(
                'application_logs_stream',
                null === $owner ? ['id' => $id] : ['id' => $id, 'owner' => $owner],
            ),
        ]);
    }

    #[Route('/applications/{id}/logs/stream', name: 'application_logs_stream', methods: ['GET'])]
    public function tail(Request $request, string $id): StreamedResponse
    {
        $owner = $this->normaliseOwner($request->query->get('owner'));

        // Eagerly resolve the Client — its factory reads session credentials.
        $logs = $this->cc->logs;

        // Release the PHP session lock so other tabs from the same browser
        // aren't blocked by this long-lived stream. We don't touch the
        // Symfony Session bag (that would clear `$_SESSION` and break the
        // response cycle later).
        if (\PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }

        $response = new StreamedResponse(function () use ($logs, $id, $owner): void {
            @set_time_limit(0);
            ignore_user_abort(false);
            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            // Immediate handshake: tells the browser EventSource the connection
            // is alive even if no logs flow for a while (e.g. idle app).
            echo "event: connected\ndata: {}\n\n";
            flush();

            $lastHeartbeat = time();
            try {
                $stream = $logs->stream($id, $owner);
                foreach ($stream as $entry) {
                    if (connection_aborted()) {
                        break;
                    }

                    $frame = json_encode([
                        'message' => $entry->message,
                        'severity' => $entry->severity,
                        'date' => $entry->date,
                        'instanceId' => $entry->instanceId,
                        'stream' => $entry->stream,
                    ], JSON_THROW_ON_ERROR);

                    echo 'data: '.$frame."\n\n";
                    flush();

                    if (time() - $lastHeartbeat >= 15) {
                        echo ": heartbeat\n\n";
                        flush();
                        $lastHeartbeat = time();
                    }
                }
            } catch (NotFoundException $e) {
                $frame = json_encode([
                    'error' => 'Log stream endpoint not found (404). Either the app id is wrong, '
                        .'or the token lacks the `application:read` scope.',
                ], JSON_THROW_ON_ERROR);
                echo 'event: stream-error'."\n".'data: '.$frame."\n\n";
                flush();
            } catch (CleverCloudException $e) {
                $frame = json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR);
                echo 'event: stream-error'."\n".'data: '.$frame."\n\n";
                flush();
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        // `private` tells Symfony's session listener to leave the response
        // alone (don't write a session_start in onKernelResponse), and
        // `no-store` keeps intermediaries from buffering or replaying SSE.
        $response->headers->set('Cache-Control', 'private, no-cache, no-store, must-revalidate');
        // Disables proxy/server buffering (nginx, Symfony local server)
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }

    private function normaliseOwner(mixed $raw): ?string
    {
        if (!\is_string($raw) || '' === $raw || 'self' === $raw) {
            return null;
        }
        if (str_starts_with($raw, 'user_')) {
            return null;
        }

        return $raw;
    }
}
