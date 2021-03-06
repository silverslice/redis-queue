<?php

namespace Silverslice\RedisQueue;

class Worker
{
    /** @var int Time to wait for new messages (BLOCK in Redis), in milliseconds */
    public $blockTimeout = 5000;

    /** @var Connection Connection */
    protected $connection;
    protected $queue;

    protected $shouldExit = false;

    protected $debug = false;

    /** @var callable */
    protected $failedCallback;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->queue = new Queue($connection);
    }

    public function run(): void
    {
        $this->debug('Worker started: ' . $this->connection->consumer);

        $this->registerSignalHandler();

        while (!$this->shouldExit) {
            $data = $this->connection->get($this->blockTimeout);
            if ($data) {
                $this->handle($data);
            } else {
                $this->debug('Block timeout reached, next loop');
            }
        }
    }

    /**
     * Sets callback for failed jobs.
     * Will be executed if job is not retryable
     *
     * @param callable $callback
     */
    public function onFail(callable $callback): void
    {
        $this->failedCallback = $callback;
    }

    /**
     * Enables or disables debug messages
     *
     * @param bool $val
     */
    public function setDebug(bool $val): void
    {
        $this->debug = $val;
    }

    protected function registerSignalHandler(): void
    {
        pcntl_async_signals(true);

        foreach ([SIGINT, SIGTERM, SIGHUP] as $sig) {
            pcntl_signal($sig, function () {
                $this->shouldExit = true;
                $this->debug("Worker stopped");
            });
        }
    }

    /**
     * Handles message received from Redis
     *
     * @param array $message
     * @return void
     */
    protected function handle(array $message): void
    {
        $this->debug('Received message: id=' . $message['id'] . ', message=' . $message['message']);

        $data = json_decode($message['message'], true);

        /** @var AbstractJob|false $job */
        $job = unserialize($data['job']);
        if ($job === false) {
            $this->debug('Unable to serialize job: ' . $data['job']);
            return;
        }
        $headers = $data['headers'];
        $retries = $headers['retries'] ?? 0;

        try {
            $this->debug('Execute job, retries=' . $retries);

            $job->execute();
            $this->connection->ack($message['id']);

            $this->debug('Job is done');
        } catch (\Throwable $e) {
            $this->connection->ack($message['id']);
            $retries = $retries + 1;
            if ($job->isRetryable($retries)) {
                $delay = $job->getRetryDelay($retries);
                $this->debug("Job failed. Redeliver with delay $delay, retry $retries");
                $this->queue->pushWithDelay($job, $delay, false, ['retries' => $retries]);
            } else { // not retryable
                $this->debug('Job failed. Not retryable, reject');

                if ($this->failedCallback) {
                    $func = $this->failedCallback;
                    $func($job, $e);
                }
            }
        }
    }

    protected function debug($msg): void
    {
        if ($this->debug) {
            $date = date('Y-m-d H:i:s');
            $pid = getmypid();
            echo "[$date] [$pid] $msg" . PHP_EOL;
        }
    }
}
