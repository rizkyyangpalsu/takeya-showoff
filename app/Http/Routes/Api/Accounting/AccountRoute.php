<?php

namespace App\Http\Routes\Api\Accounting;

use Dentro\Yalr\BaseRoute;
use App\Http\Controllers\Api\Accounting\AccountController;

class AccountRoute extends BaseRoute
{
    /**
     * Route path prefix.
     *
     * @var string
     */
    protected string $prefix = 'accounting/account';

    /**
     * Registered route name.
     *
     * @var string
     */
    protected string $name = 'api.accounting.account';

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        $this->router->get($this->prefix('{account_hash}/entry'), [
            'as' => $this->name('entry'),
            'uses' => $this->uses('entry'),
            'middleware' => [
                'permission:manage accounting',
            ],
        ]);

        $this->router->delete($this->prefix('{account_hash}'), [
            'as' => $this->name('destroy'),
            'uses' => $this->uses('destroy'),
            'middleware' => [
                'permission:manage accounting',
            ],
        ]);

        $this->router->put($this->prefix('{account_hash}'), [
            'as' => $this->name('update'),
            'uses' => $this->uses('update'),
            'middleware' => [
                'permission:manage accounting',
            ],
        ]);

        $this->router->get($this->prefix('{account_hash}'), [
            'as' => $this->name('show'),
            'uses' => $this->uses('show'),
            'middleware' => [
                'permission:manage accounting',
            ],
        ]);

        $this->router->post($this->prefix(), [
            'as' => $this->name('store'),
            'uses' => $this->uses('store'),
            'middleware' => [
                'permission:manage accounting',
            ],
        ]);

        $this->router->get($this->prefix(), [
            'as' => $this->name,
            'uses' => $this->uses('index'),
        ]);
    }

    /**
     * Get controller namespace.
     *
     * @return string
     */
    public function controller(): string
    {
        return AccountController::class;
    }
}
