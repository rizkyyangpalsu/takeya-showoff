<?php

namespace App\Exceptions\Schedule;

use Exception;
use App\Concerns\BasicResponse;
use Illuminate\Http\JsonResponse;

class ScheduleNotFound extends Exception
{
    use BasicResponse;

    public function report(): bool
    {
        return false;
    }

    public function render(): JsonResponse
    {
        return $this->success([], 'EMPTY_SCHEDULE');
    }
}
