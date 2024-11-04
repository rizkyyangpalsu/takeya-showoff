<?php

namespace App\Http\Routes\Api;

use App\Actions\Notification\GetUserNotifications;
use App\Actions\Notification\ReadNotification;
use Dentro\Yalr\BaseRoute;

class NotificationRoute extends BaseRoute
{
    /**
     * Registered route name.
     *
     * @var string
     */
    protected string $name = 'api.notification';

    /**
     * Route path prefix.
     *
     * @var string
     */
    protected string $prefix = '/notification';

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        $this->router->get($this->prefix('/'), [
            'as' => $this->name(),
            'uses' => GetUserNotifications::class,
        ]);

        $this->router->put($this->prefix('{notification}/read'), [
            'as' => $this->name('read'),
            'uses' => ReadNotification::class,
        ]);
    }
}
