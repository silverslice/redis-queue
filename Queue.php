<?php

namespace Silverslice\RedisQueue;

class Queue
{
    /** @var Connection Connection */
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function push(AbstractJob $job, array $headers = []): void
    {
        $msg = [
            'job' => serialize($job),
            'headers' => $headers,
        ];
        $this->connection->add(json_encode($msg));
    }

    public function pushWithDelay(AbstractJob $job, $delayInSeconds, $overwrite = false, $headers = [])
    {
        $msg = [
            'job' => serialize($job),
            'headers' => $headers,
        ];
        if (!$overwrite) {
            $msg['id'] = uniqid();
        }
        $this->connection->addWithDelay(json_encode($msg), $delayInSeconds);
    }
}
