<?php

namespace App\Http\Routes\Api\Accounting;

use Dentro\Yalr\BaseRoute;
use App\Http\Controllers\Api\Accounting\StatementController;

class StatementRoute extends BaseRoute
{
    /**
     * Route path prefix.
     *
     * @var string
     */
    protected string $prefix = 'accounting/statement';

    /**
     * Registered route name.
     *
     * @var string
     */
    protected string $name = 'api.accounting.statement';

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
        $this->router->get($this->prefix('balance-sheet'), [
            'as' => $this->name('balance-sheet'),
            'uses' => $this->uses('balanceSheet'),
        ]);

        $this->router->get($this->prefix('cash-flow'), [
            'as' => $this->name('cash-flow'),
            'uses' => $this->uses('cashFlow'),
        ]);

        $this->router->get($this->prefix('income'), [
            'as' => $this->name('income'),
            'uses' => $this->uses('income'),
        ]);
    }

    /**
     * Get controller namespace.
     *
     * @return string
     */
    public function controller(): string
    {
        return StatementController::class;
    }
}
