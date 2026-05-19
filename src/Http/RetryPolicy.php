<?php

namespace CleverCloud\Sdk\Http;

use CleverCloud\Sdk\Exception\ConfigurationException;

final readonly class RetryPolicy
{
    public function __construct(
        public int $maxAttempts = 3,
        public int $baseDelayMs = 200,
        public float $multiplier = 2.0,
        public int $jitterMs = 100,
        public int $maxDelayMs = 5_000,
    ) {
        if ($maxAttempts < 1) {
            throw new ConfigurationException('maxAttempts must be >= 1');
        }
        if ($baseDelayMs < 0 || $jitterMs < 0 || $maxDelayMs < 0) {
            throw new ConfigurationException('retry delays must be >= 0');
        }
        if ($multiplier < 1.0) {
            throw new ConfigurationException('multiplier must be >= 1.0');
        }
    }

    /**
     * Returns the back-off delay (in milliseconds) to wait before the given attempt number.
     * `$attempt` is 1-based: attempt 1 is the first retry after the initial try.
     */
    public function delayFor(int $attempt): int
    {
        $delay = (int) ($this->baseDelayMs * ($this->multiplier ** max(0, $attempt - 1)));
        $delay = min($delay, $this->maxDelayMs);

        if ($this->jitterMs > 0) {
            $delay += random_int(0, $this->jitterMs);
        }

        return $delay;
    }

    public static function none(): self
    {
        return new self(maxAttempts: 1, baseDelayMs: 0, multiplier: 1.0, jitterMs: 0);
    }
}
