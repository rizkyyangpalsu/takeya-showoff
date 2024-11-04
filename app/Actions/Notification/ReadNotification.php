<?php

namespace App\Actions\Notification;

use App\Concerns\BasicResponse;
use Illuminate\Notifications\DatabaseNotification;
use Lorisleiva\Actions\Concerns\AsAction;

class ReadNotification
{
    use AsAction, BasicResponse;

    public function asController(DatabaseNotification $notification): \Illuminate\Http\JsonResponse
    {
        $notification->markAsRead();

        return $this->success($notification);
    }
}
