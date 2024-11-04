<?php

namespace App\Http\Routes;

use Dentro\Yalr\BaseRoute;
use App\Http\Controllers\DefaultController;

class DefaultRoute extends BaseRoute
{
    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        $this->router->get($this->prefix(), [
            'as' => $this->name(),
            'uses' => $this->uses('index'),
        ]);

        $this->router->get($this->prefix('dashboard'), [
            'as' => $this->name('dashboard'),
            'uses' => $this->uses('dashboard'),
            'middleware' => ['auth:sanctum'],
        ]);

        $this->router->get($this->prefix('attachment/{attachment_hash}'), [
            'as' => $this->name('attachment'),
            'uses' => $this->uses('attachment'),
        ]);
    }

    /**
     * Controller used by this route.
     *
     * @return string
     */
    public function controller(): string
    {
        return DefaultController::class;
    }
}
