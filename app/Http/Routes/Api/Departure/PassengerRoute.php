<?php

namespace App\Http\Routes\Api\Departure;

use Dentro\Yalr\BaseRoute;
use App\Actions\Departure\Passenger\GetPassengersFromDeparture;

class PassengerRoute extends BaseRoute
{
    /**
     * Route path prefix.
     *
     * @var string
     */
    protected string $prefix = 'departure/{departure_hash}/passenger';

    /**
     * Registered route name.
     *
     * @var string
     */
    protected string $name = 'api.departure.passenger';

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        $this->router->get($this->prefix, [
            'as' => $this->name,
            'uses' => GetPassengersFromDeparture::class,
        ]);
    }
}
