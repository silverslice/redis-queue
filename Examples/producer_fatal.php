<?php

use Silverslice\RedisQueue\Connection;
use Silverslice\RedisQueue\Examples\Jobs\FatalJob;
use Silverslice\RedisQueue\Queue;
use Silverslice\RedisQueue\Examples\Jobs\TestJob;

require_once __DIR__ . '/../vendor/autoload.php';

$conn = new Connection();
$queue = new Queue($conn);

// send message that crashes worker
$job = new FatalJob();
$job->message = 'Message with Fatal error';
$queue->push($job);

$date = date('Y-m-d H:i:s');
echo "[$date] Fatal message sent\n";
