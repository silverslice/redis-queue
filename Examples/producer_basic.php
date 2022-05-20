<?php

use Silverslice\RedisQueue\Connection;
use Silverslice\RedisQueue\Queue;
use Silverslice\RedisQueue\Examples\Jobs\TestJob;

require_once __DIR__ . '/../vendor/autoload.php';

$conn = new Connection();
$queue = new Queue($conn);

// send message
$job = new TestJob();
$job->message = 'My base message';
$queue->push($job);

$date = date('Y-m-d H:i:s');
echo "[$date] Message sent\n";
