<?php

namespace Tests\Feature\Api;

use App\Models\Route\Track\Point;
use Tests\TestCase;
use App\Models\User;
use App\Models\Fleet;
use App\Models\Departure;
use App\Models\Schedule\Reservation;
use Database\Seeders\FleetsTableSeeder;
use Database\Seeders\DeparturesTableSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Database\Seeders\TransactionsTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DepartureApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public bool $seed = true;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCanCreateDepartureSchedule()
    {
        $this->seed(TransactionsTableSeeder::class);
        $this->seed(FleetsTableSeeder::class);
        $this->actingAs($this->getUser());

        $this->assertDatabaseCount('schedule_reservations', 2);
        $this->assertDatabaseCount('transactions', 4);
        $this->assertDatabaseCount('fleets', 20);

        /** @var Reservation $reservation */
        $reservation = Reservation::query()->first();
        /** @var Fleet $fleet */
        $fleet = Fleet::query()->first();

        $crews = array_map(fn () => [
            'staff_hash' => User::query()->where('user_type', User::USER_TYPE_STAFF_BUS)->inRandomOrder()->first()->hash,
            'stipend' => $this->faker()->randomNumber(6),
            'role' => $this->faker()->randomElement(Departure\Crew::getCrewRoles()),
        ], range(0, $this->faker()->randomNumber(1)));

        $response = $this->postJson(route('api.departure.store'), [
            'reservation_hash' => $reservation->hash,
            'origin_hash' => Point::idToHash($originId = $reservation->trips->first()->origin_id),
            'destination_hash' => Point::idToHash($destinationId = $reservation->trips->get(ceil($reservation->trips->count() / 2))->first()->destination_id),
            'fleet_hash' => $fleet->hash,
            'type' => Departure::DEPARTURE_TYPE_SCHEDULED,
            'status' => Departure::DEPARTURE_STATUS_PLANNED,
            'crews' => $crews,
        ]);

        $response->assertActionSuccess();
        $this->assertDatabaseCount('departures', 1);
        $this->assertDatabaseCount('departure_crews', count($crews));
        self::assertSame($originId, Point::hashToId($response->json('data.origin.hash')));
        self::assertSame($destinationId, Point::hashToId($response->json('data.destination.hash')));
    }

    public function testCanUpdateDepartureSchedule()
    {
        $this->seed(DeparturesTableSeeder::class);
        $this->actingAs($this->getUser());

        $this->assertDatabaseCount('departures', 1);

        $response = $this->getJson(route('api.departure'));
        $response->assertOk();

        $departureHash = $response->json('data.0.hash');

        /** @var Fleet $fleet */
        $fleet = Fleet::query()->inRandomOrder()->first();

        $data = [
            'fleet_hash' => $fleet->hash,
            'distance' => $this->faker()->randomNumber(2),
            'status' => Departure::DEPARTURE_STATUS_COMPLETED,
        ];

        $response = $this->putJson(route('api.departure.update', [
            'departure_hash' => $departureHash,
        ]), $data);

        $response->assertActionSuccess();
        self::assertSame($data['fleet_hash'], $response->json('data.fleet.hash'));
        self::assertSame($data['distance'], $response->json('data.distance'));
        self::assertSame($data['status'], $response->json('data.status'));
    }

    public function testCanGetAllDepartures()
    {
        $this->seed(DeparturesTableSeeder::class);
        $this->actingAs($this->getUser());

        $this->assertDatabaseCount('departures', 1);

        $response = $this->getJson(route('api.departure'));

        $response->assertJsonStructureIsFullPaginate();
        $response->assertJsonCount(1, 'data');

        /**
         * Test filter.
         */
        $this->seed(DeparturesTableSeeder::class);

        $this->assertDatabaseCount('departures', 2);
        Departure::query()->inRandomOrder()->first()->update(['status' => Departure::DEPARTURE_STATUS_COMPLETED]);

        $response = $this->getJson(route('api.departure', ['status' => Departure::DEPARTURE_STATUS_COMPLETED]));

        $response->assertJsonCount(1, 'data');

        $response = $this->getJson(route('api.departure', ['type' => Departure::DEPARTURE_TYPE_OTHER]));

        $response->assertJsonCount(0, 'data');

        $response = $this->getJson(route('api.departure', ['keyword' => Reservation::query()->inRandomOrder()->first()->code]));

        $response->assertJsonCount(1, 'data');
    }

    public function testCanGetDetailDeparture()
    {
        $this->seed(DeparturesTableSeeder::class);
        $this->actingAs($this->getUser());

        $this->assertDatabaseCount('departures', 1);

        $response = $this->getJson(route('api.departure.show', [
            'departure_hash' => Departure::query()->first()->hash,
        ]));

        $response->assertActionSuccess();
    }

    public function testCanDeleteDeparture()
    {
        $this->seed(DeparturesTableSeeder::class);
        $this->actingAs($this->getUser());

        /** @var Departure $departure */
        $departure = Departure::query()->first();

        $response = $this->deleteJson(route('api.departure.destroy', [
            'departure_hash' => $departure->hash,
        ]));

        $response->assertActionSuccess();
        $this->assertDatabaseHas('departures', [
            'id' => $departure->id,
        ]);
        $this->assertSoftDeleted('departures', [
            'id' => $departure->id,
        ]);
    }
}
