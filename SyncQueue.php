<?php

namespace Silverslice\RedisQueue;

/**
 * SyncQueue executes job synchronously
 */
class SyncQueue extends Queue
{
    public function push(AbstractJob $job, $headers = []): void
    {
        $job->execute();
    }
}
