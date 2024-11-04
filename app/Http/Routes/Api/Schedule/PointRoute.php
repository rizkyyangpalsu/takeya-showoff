<?php

namespace App\Http\Routes\Api\Schedule;

use Dentro\Yalr\BaseRoute;
use App\Http\Controllers\Api\Schedule\PointController;

class PointRoute extends BaseRoute
{
    protected string $prefix = 'schedule/point';

    protected string $name = 'api.schedule.point';

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        $this->router->get($this->prefix, [
            'as' => $this->name,
            'uses' => $this->uses('index'),
        ])->withoutMiddleware('auth:sanctum');

        $this->router->get($this->prefix('{point_hash}/destination'), [
            'as' => $this->name('destination'),
            'uses' => $this->uses('getDestination'),
        ])->withoutMiddleware('auth:sanctum');

        $this->router->get($this->prefix('{point_hash}'), [
            'as' => $this->name('show'),
            'uses' => $this->uses('show'),
        ]);

        $this->router->group(['middleware' => 'permission:manage point'], function () {
            $this->router->post($this->prefix, [
                'as' => $this->name('store'),
                'uses' => $this->uses('store'),
            ]);

            $this->router->put($this->prefix('{point_hash}'), [
                'as' => $this->name('update'),
                'uses' => $this->uses('update'),
            ]);

            $this->router->delete($this->prefix('{point_hash}'), [
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
        return PointController::class;
    }
}
