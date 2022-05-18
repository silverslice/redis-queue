<?php

namespace Silverslice\RedisQueue\Examples\Jobs;

use Silverslice\RedisQueue\AbstractJob;

class FatalJob extends AbstractJob
{
    public $message;

    public function execute(): void
    {
        $this->realSleep(2);

        ini_set('memory_limit', '16M');
        $str = '123456789';
        while (true) {
            $str .= $str;
        }
    }

    public function isRetryable($retries): bool
    {
        return $retries <= 5;
    }

    public function getRetryDelay($retries): int
    {
        return 1000 * 2 ** ($retries - 1);
    }

    protected function realSleep(int $seconds): bool
    {
        $period = ['seconds' => $seconds, 'nanoseconds' => 0];

        while (array_sum($period) > 0) {
            $period = time_nanosleep($period['seconds'], $period['nanoseconds']);

            if (is_bool($period)) {
                return $period;
            }
        }

        return true;
    }
}
