<?php

use Silverslice\RedisQueue\Connection;
use Silverslice\RedisQueue\SystemWorker;

require_once __DIR__ . '/../vendor/autoload.php';

$conn = new Connection();
$worker = new SystemWorker($conn);
$worker->maxFailures = 3;
$worker->setDebug(true);

// if job crashes more than maxFailures times
$worker->onFail(function($message, $id) {
    echo '[!] Message rejected: ' . $message . ' (id='. $id .')' . PHP_EOL;
});
$worker->run();

