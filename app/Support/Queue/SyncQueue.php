<?php

namespace App\Support\Queue;

trait SyncQueue
{
    private static function forceQueueSync(callable $call)
    {
        $original = config()->get('queue.default');
        config()->set('queue.default', 'sync');

        $call();

        config()->set('queue.default', $original);
    }
}
