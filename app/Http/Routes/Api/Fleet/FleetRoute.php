<?php

namespace App\Http\Routes\Api\Fleet;

use Dentro\Yalr\BaseRoute;
use App\Http\Controllers\Api\Fleet\FleetController;

class FleetRoute extends BaseRoute
{
    /**
     * Route path prefix.
     *
     * @var string
     */
    protected string $prefix = '/fleet';

    /**
     * Registered route name.
     *
     * @var string
     */
    protected string $name = 'api.fleet';

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        $this->router->group(['middleware' => 'permission:manage fleet'], function () {
            $this->router->delete($this->prefix('{fleet_hash}'), [
                'as' => $this->name('destroy'),
                'uses' => $this->uses('destroy'),
            ]);

            $this->router->put($this->prefix('{fleet_hash}'), [
                'as' => $this->name('update'),
                'uses' => $this->uses('update'),
            ]);

            $this->router->post($this->prefix(), [
                'as' => $this->name('store'),
                'uses' => $this->uses('store'),
            ]);
        });

        $this->router->get($this->prefix('{fleet_hash}'), [
            'as' => $this->name('show'),
            'uses' => $this->uses('show'),
        ]);

        $this->router->get($this->prefix(), [
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
        return FleetController::class;
    }
}
