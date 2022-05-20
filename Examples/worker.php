<?php

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

