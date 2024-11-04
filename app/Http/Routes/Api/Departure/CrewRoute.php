<?php

namespace App\Http\Routes\Api\Departure;

use Dentro\Yalr\BaseRoute;
use App\Http\Controllers\Api\Departure\CrewController;

class CrewRoute extends BaseRoute
{
    protected string $prefix = 'departure/{departure_hash}/crew';

    protected string $name = 'api.departure.crew';

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        $this->router->group(['middleware' => 'permission:manage departure crew'], function () {
            $this->router->post($this->prefix('{staff_hash}/assign'), [
                'as' => $this->name('assign'),
                'uses' => $this->uses('store'),
            ]);

            $this->router->put($this->prefix('{crew_hash}/update'), [
                'as' => $this->name('update'),
                'uses' => $this->uses('update'),
            ]);

            $this->router->delete($this->prefix('{crew_hash}/detach'), [
                'as' => $this->name('detach'),
                'uses' => $this->uses('destroy'),
            ]);
        });

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
        return CrewController::class;
    }
}
