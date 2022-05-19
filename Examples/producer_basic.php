<?php

use Silverslice\RedisQueue\Connection;
use Silverslice\RedisQueue\Queue;
use Silverslice\RedisQueue\Examples\Jobs\TestJob;

require_once __DIR__ . '/../vendor/autoload.php';

$conn = new Connection();
$queue = new Queue($conn);

// send n messages, message number 3 will not be executed
$n = 4;
for ($i = 1; $i <= $n; $i++) {
    $job = new TestJob();
    if ($i === 3) {
        $job->isFailed = true;
        $job->message = 'My message ' . $i . ', failed';
    } else {
        $job->message = 'My message ' . $i;
    }

    $queue->push($job);
}

$date = date('Y-m-d H:i:s');
echo "[$date] $n messages sent\n";
