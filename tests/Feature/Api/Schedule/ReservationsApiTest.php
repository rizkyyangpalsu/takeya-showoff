<?php

namespace Tests\Feature\Api\Schedule;

use App\Http\Controllers\Api\Schedule\ReservationController;
use Tests\TestCase;
use App\Models\Schedule\Reservation;
use Database\Seeders\DeparturesTableSeeder;
use Database\Seeders\TransactionsTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReservationsApiTest extends TestCase
{
    use RefreshDatabase;

    public bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TransactionsTableSeeder::class);
        $this->actingAs($this->getUser());
    }

    public function testCanGetAllReservations()
    {
        $response = $this->getJson(route('api.schedule.reservation'));

        $response->assertJsonStructureIsFullPaginate();

        $response->assertJsonCount(2, 'data');

        /**
         * Test filter by keyword.
         */

        /** @var Reservation $reservation */
        $reservation = Reservation::query()->inRandomOrder()->first();

        $response = $this->getJson(route('api.schedule.reservation', [
            'keyword' => $reservation->code,
        ]));

        $response->assertJsonCount(1, 'data');

        /**
         * Test filter by has_departure.
         */
        $this->seed(DeparturesTableSeeder::class);

        $this->assertDatabaseCount('departures', 1);

        $response = $this->getJson(route('api.schedule.reservation', [
            'has_departure' => true,
        ]));

        $response->assertJsonCount(1, 'data');

        $response = $this->getJson(route('api.schedule.reservation', [
            'has_departure' => false,
        ]));

        $response->assertJsonCount(1, 'data');


        /**
         * Test sorting.
         */
        $this->assertDatabaseCount('schedule_reservations', 2);
        $response = $this->getJson(route('api.schedule.reservation', [
            'order' => [
                [
                    'field' => 'code',
                    'direction' => 'desc',
                ],
            ],
        ]));

        $response->assertJsonStructureIsFullPaginate();
        $this->assertTrue($response->json('data.0.code') > $response->json('data.1.code'));

        $this->assertDatabaseCount('schedule_reservations', 2);
        $response = $this->getJson(route('api.schedule.reservation', [
            'order' => [
                [
                    'field' => 'code',
                    'direction' => 'asc',
                ],
            ],
        ]));

        $response->assertJsonStructureIsFullPaginate();
        $this->assertTrue($response->json('data.0.code') < $response->json('data.1.code'));
    }

    public function testCanGetDetailReservation()
    {
        $listResponse = $this->getJson(route('api.schedule.reservation'));
        $listResponse->assertJsonCount(2, 'data');

        $response = $this->getJson(route('api.schedule.reservation.show', [
            'reservation_hash' => $listResponse->json('data.0.hash'),
        ]));

        $response->assertActionSuccess();
    }

    public function testCanGetTripBookers(): void
    {
        /** @var Reservation $reservation */
        $reservation = Reservation::query()->first();

        $response = $this->getJson(action([ReservationController::class, 'getBookers'], [
            'reservation_hash' => $reservation->hash,
            'trip_hash' => $reservation->trips()->first()->hash,
        ]));

        $response->assertActionSuccess();
    }
}
