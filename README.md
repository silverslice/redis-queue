Queue for Redis based on streams
============================================================

## Requirements

- Redis >= 6.2.0
- phpredis PHP extension

## Install

`composer require silverslice/redis-queue`

## Features
- Push messages with delay
- Individual retry strategy for each job
- Correct processing of tasks that terminate due to lack of memory

## Usage

Create Job class:

```php

namespace Silverslice\RedisQueue\Examples\Jobs;

use Silverslice\RedisQueue\AbstractJob;

class TestJob extends AbstractJob
{
    public $message;
    
    public function execute()
    {
        echo $this->message . ' ' . date('H:i:s') . "\n";
    }
}
```

Push job to the queue:
```php

use Silverslice\RedisQueue\Connection;
use Silverslice\RedisQueue\Queue;
use Silverslice\RedisQueue\Examples\Jobs\TestJob;

require __DIR__ . '/../vendor/autoload.php';

// create connection to Redis
$conn = new Connection();
$queue = new Queue($conn);

// create job
$job = new TestJob();
$job->message = 'My message';

// push to the queue
$queue->push($job);

// push to the queue with delay 2 seconds
$queue->pushWithDelay($job, 2);

```

Run worker:
```php

use Silverslice\RedisQueue\AbstractJob;
use Silverslice\RedisQueue\Connection;
use Silverslice\RedisQueue\Worker;

require_once __DIR__ . '/../vendor/autoload.php';

// each consumer in stream need unique name, so we pass name as argument on start worker
$options = getopt('', ['name:']);
if (!isset($options['name'])) {
    echo 'Usage: php worker.php --name worker_name' . PHP_EOL;
    exit(1);
}

$conn = new Connection();
$conn->consumer = $options['name'];
$worker = new Worker($conn);
$worker->setDebug(true);
$worker->onFail(function (AbstractJob $job, \Throwable $e) {
    echo '[!] Job failed: ' . serialize($job) . PHP_EOL;
    echo '[.] Error: ' . $e->getMessage() . PHP_EOL;
});
$worker->run();

```

You can set individual retry logic in the job class.
Default behaviour: maximum 5 retries, delay between retries is
1 second with multiplier 2 (1, 2, 4, 8, 16 seconds).

```php

class TestJob extends AbstractJob
{
    public $message;

    public function execute()
    {
        
    }

    /**
     * Is job retryable?
     *
     * @param int $retries Number of retry
     * @return bool
     */
    public function isRetryable($retries): bool
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
```

To overwrite job delay pass true as third argument in `pushWithDelay`:
```php
use Silverslice\RedisQueue\Connection;
use Silverslice\RedisQueue\Queue;
use Silverslice\RedisQueue\Examples\Jobs\TestJob;

require_once __DIR__ . '/../vendor/autoload.php';

$conn = new Connection();
$queue = new Queue($conn);

// send message with delay 10 seconds, we are going to change delay later
$job = new TestJob();
$job->message = 'Message with delay';
$queue->pushWithDelay($job, 10, true);

// overwrite delay
$queue->pushWithDelay($job, 15, true);

$date = date('Y-m-d H:i:s');
echo "[$date] Message sent\n";
```

For testing / local development SyncQueue class may be useful.
SyncQueue executes job synchronously:

```php

$queue = new SyncQueue($connection);

$job = new TestJob();
$job->message = 'My first job';

// will be executed synchronously
$queue->push($job);

```

See `Examples` directory for more samples.