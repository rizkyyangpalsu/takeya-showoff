<?php

namespace App\Http\Routes\Api\Schedule;

use Dentro\Yalr\BaseRoute;
use App\Http\Controllers\Api\Schedule\ReservationController;

class ReservationRoute extends BaseRoute
{
    protected string $prefix = 'schedule/reservation';

    protected string $name = 'api.schedule.reservation';

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

        $this->router->group(['middleware' => 'permission:manage reservation'], function () {
            $this->router->get($this->prefix('{reservation_hash}'), [
                'as' => $this->name('show'),
                'uses' => $this->uses('show'),
            ]);

            $this->router->get($this->prefix('{reservation_hash}/{trip_hash}/booker'), [
                'as' => $this->name('booker'),
                'uses' => $this->uses('getBookers'),
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
        return ReservationController::class;
    }
}
