<?php

namespace App\Http\Routes\Api;

use Dentro\Yalr\BaseRoute;
use App\Actions\User\UpdateUserPassword;
use App\Http\Controllers\Api\ProfileController;

class ProfileRoute extends BaseRoute
{
    /**
     * Registered route name.
     *
     * @var string
     */
    protected string $name = 'api.profile';

    /**
     * Route path prefix.
     *
     * @var string
     */
    protected string $prefix = '/profile';

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        $this->router->put($this->prefix(), [
            'as' => $this->name('update'),
            'uses' => $this->uses('update'),
        ]);

        $this->router->get($this->prefix(), [
            'as' => $this->name,
            'uses' => $this->uses('index'),
        ]);

        $this->router->patch($this->prefix('password'), [
            'as' => $this->name('password'),
            'uses' => UpdateUserPassword::class,
        ]);
    }

    /**
     * Controller used by this route.
     *
     * @return string
     */
    public function controller(): string
    {
        return ProfileController::class;
    }
}
