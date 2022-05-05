<?php

namespace Silverslice\RedisQueue;

class SystemWorker
{
    /**
     * @var int Max tries to redeliver pending message. After that the message will be acknowledged.
     */
    public $maxFailures = 3;

    /** @var Connection Connection */
    protected $connection;

    protected $shouldExit = false;

    protected $debug = false;

    /** @var callable */
    protected $failedCallback;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function run(): void
    {
        $this->debug('System worker started');

        $this->registerSignalHandler();

        while (!$this->shouldExit) {
            $this->checkPending();
            sleep(5);
        }
    }

    /**
     * Sets callback for failed jobs.
     * Will be executed if job is not retryable
     *
     * @param callable $callback
     */
    public function setFailedCallback(callable $callback): void
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

    /**
     * Checks pending messages
     *
     * @return void
     */
    protected function checkPending(): void
    {
        // get pending messages
        $pending = $this->connection->pending(100, 9000);
        if ($pending) {
            foreach ($pending as $element) {
                $this->debug('Pending message id: ' . $element[0]);
                // get message by id
                $messages = $this->connection->range($element[0], $element[0], 1);
                if (!$messages) {
                    // if message doesn't exist in stream (for example it was trimmed) - ack it
                    $this->connection->ack($element[0]);
                    $this->debug('Message not found, ack');
                    continue;
                }
                foreach ($messages as $id => $message) {
                    // decode message and increase failures counter
                    $data = json_decode($message['message'], true);
                    $failures = $data['headers']['failures'] ?? 0;
                    $failures++;

                    if ($failures > $this->maxFailures) {
                        // reject job
                        $this->connection->ack($id);
                        $this->debug('Max failures reached, reject');
                        if ($this->failedCallback) {
                            $func = $this->failedCallback;
                            $func($message['message'], $id);
                        }
                    } else {
                        // redeliver message
                        $data['headers']['failures'] = $failures;
                        $this->connection->ack($id);
                        $this->connection->add(json_encode($data));
                        $this->debug('Redeliver with failures ' . $failures);
                    }
                }
            }
        } else {
            $this->debug('No pending messages');
        }
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

    protected function debug($msg): void
    {
        if ($this->debug) {
            $date = date('Y-m-d H:i:s');
            $pid = getmypid();
            echo "[$date] [$pid] $msg" . PHP_EOL;
        }
    }
}
