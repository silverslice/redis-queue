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
}
