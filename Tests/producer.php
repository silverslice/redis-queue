<?php

use Silverslice\RedisQueue\Connection;
use Silverslice\RedisQueue\Queue;
use Silverslice\RedisQueue\Tests\Jobs\FatalJob;
use Silverslice\RedisQueue\Tests\Jobs\TestJob;

require_once __DIR__ . '/../vendor/autoload.php';

$conn = new Connection();
$queue = new Queue($conn);

$n = 4;
for ($i = 1; $i <= $n; $i++) {
    if ($i === 3) {
        $job = new FatalJob();
        //$job = new TestJob();
        //$job->isFailed = true;
    } else {
        $job = new TestJob();
    }
    $job->message = 'my message' . $i;


    $queue->push($job);
}
