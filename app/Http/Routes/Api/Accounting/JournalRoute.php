<?php

namespace App\Http\Routes\Api\Accounting;

use Dentro\Yalr\BaseRoute;
use App\Http\Controllers\Api\Accounting\JournalController;

class JournalRoute extends BaseRoute
{
    /**
     * Route path prefix.
     *
     * @var string
     */
    protected string $prefix = 'accounting/journal';

    /**
     * Registered route name.
     *
     * @var string
     */
    protected string $name = 'api.accounting.journal';

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
        $this->router->delete($this->prefix('{journal_hash}'), [
            'as' => $this->name('destroy'),
            'uses' => $this->uses('destroy'),
        ]);

        $this->router->put($this->prefix('{journal_hash}'), [
            'as' => $this->name('update'),
            'uses' => $this->uses('update'),
        ]);

        $this->router->get($this->prefix('{journal_hash}'), [
            'as' => $this->name('show'),
            'uses' => $this->uses('show'),
        ]);

        $this->router->post($this->prefix(), [
            'as' => $this->name('store'),
            'uses' => $this->uses('store'),
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
        return JournalController::class;
    }
}
