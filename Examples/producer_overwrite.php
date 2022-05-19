<?php

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
