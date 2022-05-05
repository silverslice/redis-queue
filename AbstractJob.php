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
}
