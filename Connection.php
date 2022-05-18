<?php

namespace Silverslice\RedisQueue;

/**
 * Connection to Redis
 */
class Connection
{
    public $host = '127.0.0.1';
    public $port = 6379;
    public $stream = 'mystream';
    public $group = 'mygroup';
    public $consumer = 'worker';
    public $maxLen = 1000;

    /** @var \Redis */
    protected $connection;


    public function __construct()
    {
        register_shutdown_function([$this, 'close']);
    }

    /**
     * Opens connection.
     */
    public function open(): void
    {
        $this->connection = new \Redis();
        $this->connection->connect($this->host, $this->port);
        // creates stream automatically
        $this->connection->xGroup('CREATE', $this->stream, $this->group, 0, true);
    }

    /**
     * Closes connection.
     */
    public function close(): void
    {
        if (!$this->connection) {
            return;
        }
        $this->connection->close();
    }

    public function add(string $message): void
    {
        $this->getConnection()->xAdd($this->stream, '*', ['message' => $message], $this->maxLen, true);
    }

    public function get(int $block = null): ?array
    {
        $messages = $this->getConnection()->xReadGroup(
            $this->group,
            $this->consumer,
            [$this->stream => '>'],
            1,
            $block
        );

        if (!empty($messages[$this->stream])) {
            foreach ($messages[$this->stream] as $key => $message) {
                return [
                    'id' => $key,
                    'message' => $message['message'],
                ];
            }
        }

        return null;
    }

    public function ack(string $id): int
    {
        return $this->getConnection()->xAck($this->stream, $this->group, [$id]);
    }

    public function pending(int $count, int $idle): array
    {
        return $this->getConnection()->rawCommand('XPENDING', $this->stream, $this->group, 'IDLE', $idle, '-', '+', $count);
    }

    public function range(string $start, string $end, int $count): array
    {
        return $this->getConnection()->xRange($this->stream, $start, $end, $count);
    }

    public function addWithDelay(string $message, int $delayInS): void
    {
        $score = time() + $delayInS;
        $this->getConnection()->zAdd($this->stream . '_delayed', $score, $message);
    }

    public function getDelayed(int $start, int $end): array
    {
        return $this->getConnection()->rawCommand('ZRANGE', $this->stream . '_delayed', $start, $end, 'BYSCORE');
    }

    public function removeDelayed(string $message): int
    {
        return $this->getConnection()->zRem($this->stream . '_delayed', $message);
    }

    protected function getConnection(): \Redis
    {
        if (!$this->connection) {
            $this->open();
        }

        return $this->connection;
    }
}
