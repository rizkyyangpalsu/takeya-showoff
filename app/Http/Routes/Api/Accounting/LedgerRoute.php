<?php

namespace App\Http\Routes\Api\Accounting;

use Dentro\Yalr\BaseRoute;
use App\Http\Controllers\Api\Accounting\LedgerController;

class LedgerRoute extends BaseRoute
{
    /**
     * Route path prefix.
     *
     * @var string
     */
    protected string $prefix = 'accounting/general-ledger';

    /**
     * Registered route name.
     *
     * @var string
     */
    protected string $name = 'api.accounting.general-ledger';

    /**
     * Middleware used in route.
     *
     * @var array|string
     */
    protected array|string $middleware = [
        'permission:manage accounting',
    ];

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

        $this->router->get($this->prefix(), [
            'as' => $this->name('store'),
            'uses' => $this->uses('store'),
        ]);
    }

    /**
     * Get controller namespace.
     *
     * @return string
     */
    public function controller(): string
    {
        return LedgerController::class;
    }
}
