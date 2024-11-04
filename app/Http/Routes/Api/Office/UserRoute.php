<?php

namespace App\Http\Routes\Api\Office;

use Dentro\Yalr\BaseRoute;
use App\Http\Controllers\Api\Office\UserController;

class UserRoute extends BaseRoute
{
    /**
     * Registered route name.
     *
     * @var string
     */
    protected string $name = 'api.office.user';

    /**
     * Route path prefix.
     *
     * @var string
     */
    protected string $prefix = '/office/{office_slug}/user';

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        $this->router->get($this->prefix(), [
            'as' => $this->name,
            'uses' => $this->uses('index'),
        ]);
    }

    /**
     * Controller used by this route.
     *
     * @return string
     */
    public function controller(): string
    {
        return UserController::class;
    }
}
