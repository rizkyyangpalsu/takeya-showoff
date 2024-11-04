<?php

namespace App\Actions\Notification;

use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;

class GetUserNotifications
{
    use AsAction;

    public function asController(ActionRequest $request)
    {
        $limit = $request->limit ?? 10;
        return auth()->user()->unreadNotifications()->orderByDesc('created_at')->limit($limit)->get();
    }
}
