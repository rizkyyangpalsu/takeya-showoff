<?php

namespace App\Http\Routes\Api;

use Dentro\Yalr\BaseRoute;
use App\Actions\User\GetUserOffices;
use App\Actions\User\AttachUserIntoOffice;
use App\Actions\User\DetachUserFromOffice;
use App\Http\Controllers\Api\UserController;

class UserRoute extends BaseRoute
{
    /**
     * Registered route name.
     *
     * @var string
     */
    protected string $name = 'api.user';

    /**
     * Route path prefix.
     *
     * @var string
     */
    protected string $prefix = '/user';

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        $this->router->get($this->prefix('{user_hash}'), [
            'as' => $this->name('show'),
            'uses' => $this->uses('show'),
        ]);

        $this->router->get($this->prefix(), [
            'as' => $this->name,
            'uses' => $this->uses('index'),
        ]);

        $this->router->group(['middleware' => 'permission:manage user'], function () {
            $this->router->put($this->prefix('{user_hash}'), [
                'as' => $this->name('update'),
                'uses' => $this->uses('update'),
            ]);

            $this->router->delete($this->prefix('{user_hash}'), [
                'as' => $this->name('destroy'),
                'uses' => $this->uses('destroy'),
            ]);

            $this->router->post($this->prefix(), [
                'as' => $this->name('store'),
                'uses' => $this->uses('store'),
            ]);

            $this->router->patch($this->prefix('{user_hash}/attach'), [
                'as' => $this->name('attach'),
                'uses' => AttachUserIntoOffice::class,
            ]);

            $this->router->patch($this->prefix('{user_hash}/detach'), [
                'as' => $this->name('detach'),
                'uses' => DetachUserFromOffice::class,
            ]);

            $this->router->get($this->prefix('{user_hash}/office'), [
                'as' => $this->name('office'),
                'uses' => GetUserOffices::class,
            ]);
        });
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
