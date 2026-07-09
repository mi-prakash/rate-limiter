<?php

namespace App\Services;

class InMemoryRateLimiter
{
    /**
     * Shared memory key used by sysvshm.
     *
     * This keeps counters in local machine RAM without Redis, database,
     * sessions, file cache, or external cache services.
     */
    private const SHARED_MEMORY_KEY = 0x20260706;

    /**
     * Semaphore key used to avoid race conditions while updating counters.
     */
    private const SEMAPHORE_KEY = 0x20260707;

    /**
     * Variable key inside the shared memory segment.
     */
    private const COUNTERS_VAR_KEY = 1;

    /**
     * Shared memory segment size in bytes.
     */
    private const MEMORY_SIZE = 1048576;

    /**
     * Records a request and determines whether it is allowed.
     *
     * @return array{
     *     allowed: bool,
     *     limit: int,
     *     remaining: int,
     *     retry_after: int,
     *     reset_at: int
     * }
     */
    public function hit(string $key, int $limit, int $windowSeconds): array
    {
        $now = time();
        $windowStart = $now - ($now % $windowSeconds);
        $expiresAt = $windowStart + $windowSeconds;
        $counterKey = "rate_limit:{$key}:{$windowStart}";

        $memory = shm_attach(self::SHARED_MEMORY_KEY, self::MEMORY_SIZE);
        $semaphore = sem_get(self::SEMAPHORE_KEY);

        sem_acquire($semaphore);

        try {
            // Load existing counters from shared RAM.
            $counters = shm_has_var($memory, self::COUNTERS_VAR_KEY)
                ? shm_get_var($memory, self::COUNTERS_VAR_KEY)
                : [];

            // Remove expired counters to reduce memory growth over time.
            foreach ($counters as $storedKey => $counter) {
                if ($counter['expires_at'] <= $now) {
                    unset($counters[$storedKey]);
                }
            }

            // Initialize the current fixed-window counter.
            if (! isset($counters[$counterKey])) {
                $counters[$counterKey] = [
                    'count' => 0,
                    'expires_at' => $expiresAt,
                ];
            }

            $counters[$counterKey]['count']++;

            // Persist the updated counters back to shared RAM.
            shm_put_var($memory, self::COUNTERS_VAR_KEY, $counters);

            $count = $counters[$counterKey]['count'];
        } finally {
            sem_release($semaphore);
            shm_detach($memory);
        }

        return [
            'allowed' => $count <= $limit,
            'limit' => $limit,
            'remaining' => max(0, $limit - $count),
            'retry_after' => max(1, $expiresAt - $now),
            'reset_at' => $expiresAt,
        ];
    }
}