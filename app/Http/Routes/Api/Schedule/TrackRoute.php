<?php

namespace App\Http\Routes\Api\Schedule;

use Dentro\Yalr\BaseRoute;
use App\Http\Controllers\Api\Schedule\TrackController;

class TrackRoute extends BaseRoute
{
    protected string $prefix = 'schedule/track-route';

    protected string $name = 'api.schedule.track-route';

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
        ]);

        $this->router->get($this->prefix('{route_hash}'), [
            'as' => $this->name('show'),
            'uses' => $this->uses('show'),
        ]);

        $this->router->group(['middleware' => 'permission:manage track'], function () {
            $this->router->post($this->prefix, [
                'as' => $this->name('store'),
                'uses' => $this->uses('store'),
            ]);

            $this->router->put($this->prefix('{route_hash}'), [
                'as' => $this->name('update'),
                'uses' => $this->uses('update'),
            ]);

            $this->router->delete($this->prefix('{route_hash}'), [
                'as' => $this->name('destroy'),
                'uses' => $this->uses('destroy'),
            ]);

            $this->router->post($this->prefix('{route_hash}'), [
                'as' => $this->name('duplicate'),
                'uses' => $this->uses('duplicate'),
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
        return TrackController::class;
    }
}
