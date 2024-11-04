<?php

namespace Tests\Feature\Api\Departure;

class PassengerApiTestCase extends DepartureTestCase
{
    public function testCanGetPassengerFromDeparture()
    {
        $departure = $this->getDeparture();

        $response = $this->get(route('api.departure.passenger', [
            'departure_hash' => $departure->hash,
        ]));

        $response->assertJsonStructureIsFullPaginate();
    }
}
