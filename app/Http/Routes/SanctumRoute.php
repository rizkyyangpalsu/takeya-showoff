<?php

namespace App\Http\Routes;

use Dentro\Yalr\BaseRoute;
use App\Http\Controllers\SanctumController;

class SanctumRoute extends BaseRoute
{
    /**
     * Route path prefix.
     *
     * @var string
     */
    protected string $prefix = '/sanctum';

    /**
     * Registered route name.
     *
     * @var string
     */
    protected string $name = 'sanctum';

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        // authenticate user.
        $this->router->post($this->prefix('token'), [
            'as' => $this->name('token'),
            'uses' => $this->uses('token'),
        ]);
    }

    /**
     * Controller used by this route.
     *
     * @return string
     */
    public function controller(): string
    {
        return SanctumController::class;
    }
}
