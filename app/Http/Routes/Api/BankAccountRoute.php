<?php

namespace App\Http\Routes\Api;

use App\Http\Controllers\Api\BankAccountController;
use Dentro\Yalr\BaseRoute;

class BankAccountRoute extends BaseRoute
{
    /**
     * Registered route name.
     *
     * @var string
     */
    protected string $name = 'api.bank';

    /**
     * Route path prefix.
     *
     * @var string
     */
    protected string $prefix = '/bank';

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

        $this->router->group(['middleware' => 'permission:manage bank accounts'], function () {
            $this->router->post($this->prefix(), [
                'as' => $this->name('store'),
                'uses' => $this->uses('store'),
            ]);

            $this->router->put($this->prefix('{bank_account_hash}'), [
                'as' => $this->name('update'),
                'uses' => $this->uses('update'),
            ]);

            $this->router->delete($this->prefix('{bank_account_hash}'), [
                'as' => $this->name('destroy'),
                'uses' => $this->uses('destroy'),
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
        return BankAccountController::class;
    }
}
