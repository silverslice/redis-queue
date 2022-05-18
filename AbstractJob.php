<?php

namespace Silverslice\RedisQueue;

abstract class AbstractJob
{
    abstract public function execute(): void;

    /**
     * Is job retryable?
     *
     * @param int $retries Number of retry
     * @return bool
     */
    public function isRetryable(int $retries): bool
    {
        return $retries <= 5;
    }

    /**
     * Returns retry delay in seconds
     *
     * @param $retries
     * @return int
     */
    public function getRetryDelay($retries): int
    {
        return 1 * 2 ** ($retries - 1);
    }
}
