<?php

namespace App\Http\Routes\Api\Fleet;

use Dentro\Yalr\BaseRoute;
use App\Http\Controllers\Api\Fleet\LayoutController;

class LayoutRoute extends BaseRoute
{
    protected string $prefix = 'fleet/layout';

    protected string $name = 'api.fleet.layout';

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        $this->router->group(['middleware' => 'permission:manage layout'], function () {
            $this->router->post($this->prefix, [
                'as' => $this->name('store'),
                'uses' => $this->uses('store'),
            ]);

            $this->router->put($this->prefix('{layout_hash}'), [
                'as' => $this->name('update'),
                'uses' => $this->uses('update'),
            ]);

            $this->router->delete($this->prefix('{layout_hash}'), [
                'as' => $this->name('delete'),
                'uses' => $this->uses('destroy'),
            ]);
        });

        $this->router->get($this->prefix('{layout_hash}'), [
            'as' => $this->name('show'),
            'uses' => $this->uses('show'),
        ]);

        $this->router->get($this->prefix, [
            'as' => $this->name,
            'uses' => $this->uses('index'),
        ]);
    }

    /**
     * Controller used by this route.
     *
     * @return string
     */
    public function controller(): string
    {
        return LayoutController::class;
    }
}
