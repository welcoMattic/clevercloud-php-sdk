<?php

namespace CleverCloud\Sdk\Tests\Integration;

/**
 * Verifies the historical log query — one-shot, fully assertable.
 *
 * The live SSE path is intentionally NOT exercised here because logs may be
 * silent for minutes on idle applications, which would make the test flaky.
 * The integration smoke for the live path lives in `examples/stream-logs.php`.
 */
final class LogStreamingTest extends IntegrationTestCase
{
    public function testHistoricalQueryReturnsTypedLogEntries(): void
    {
        $organisationId = $this->targetOrganisationId();
        $applicationId = $this->targetApplicationId($organisationId);

        // Look back over the last 24h with a small limit so we do not pull
        // megabytes of log lines into the test process.
        $logs = $this->client->logs->query($applicationId, $organisationId, [
            'since' => gmdate('Y-m-d\TH:i:s\Z', time() - 86400),
            'limit' => 10,
        ]);

        self::assertIsList($logs);
        self::assertLessThanOrEqual(10, \count($logs));
    }
}
