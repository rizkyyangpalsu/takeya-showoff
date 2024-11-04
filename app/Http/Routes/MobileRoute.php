<?php

namespace App\Http\Routes;

use App\Http\Controllers\Api\QRCodeController;
use Dentro\Yalr\BaseRoute;
use App\Http\Controllers\SanctumController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;

class MobileRoute extends BaseRoute
{
    protected string $prefix = 'mobile';

    protected array|string $middleware = ['api', 'throttle:api'];

    protected string $name = 'api-mobile';

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        $this->router->get($this->prefix('qr/{code}'), [
            'as' => $this->name('qr'),
            'uses' => $this->uses('index', QRCodeController::class),
        ])->where('code', '(.*)');

        $this->router->post($this->prefix('login'), [
            'as' => $this->name('login'),
            'uses' => $this->uses('token', SanctumController::class),
        ]);

        $this->router->post($this->prefix('register'), [
            'as' => $this->name('register'),
            'uses' => $this->uses('store', RegisteredUserController::class),
        ]);

        $this->router->post($this->prefix('forgot-password'), [
            'as' => $this->name('password.email'),
            'uses' => $this->uses('store', PasswordResetLinkController::class),
        ]);
    }
}
