<?php

namespace App\Http\Routes\Api;

use Tightenco\Ziggy\Ziggy;
use Dentro\Yalr\BaseRoute;

class ZiggyRoute extends BaseRoute
{
    /**
     * Route path prefix.
     *
     * @var string
     */
    protected string $prefix = '/';

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        $this->router->get($this->prefix('api-route-list'), fn () => response()->json(new Ziggy));
    }
}
