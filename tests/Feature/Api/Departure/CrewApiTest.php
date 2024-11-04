<?php

namespace Tests\Feature\Api\Departure;

use App\Models\User;
use App\Models\Departure;
use Database\Seeders\DeparturesTableSeeder;

class CrewApiTest extends DepartureTestCase
{
    public function testCanGetCrewFromDeparture()
    {
        $departure = $this->getDeparture();

        $response = $this->get(route('api.departure.crew', [
            'departure_hash' => $departure->hash,
        ]));

        $response->assertJsonStructureIsFullPaginate();
        $response->assertJsonCount(DeparturesTableSeeder::$CREW_TOTAL, 'data');
    }

    public function testCanAssignStaffToCrew()
    {
        $departure = $this->getDeparture();
        $staff = $this->getStaff();
        $user = User::query()->find($staff->id);

        $this->assertEquals($user->hash, $staff->hash);

        $response = $this->postJson(route('api.departure.crew.assign', [
            'departure_hash' => $departure->hash,
            'staff_hash' => $user->hash,
        ]), [
//            'stipend' => $this->faker()->randomNumber(6),
            'role' => $this->faker()->randomElement(Departure\Crew::getCrewRoles()),
        ]);

        $response->assertActionSuccess();
    }

    public function testCanUpdateExistingCrew()
    {
        $departure = $this->getDeparture();
        $staff = $this->getStaff();

        /** @var \App\Models\Departure\Crew $crew */
        $crew = $departure->crews()->first();

        $data = [
            'staff_hash' => $staff->hash,
            'stipend' => $this->faker()->randomNumber(5),
            'role' => $this->faker()->randomElement(Departure\Crew::getCrewRoles()),
        ];

        $response = $this->putJson(route('api.departure.crew.update', [
            'departure_hash' => $departure->hash,
            'crew_hash' => $crew->hash,
        ]), $data);

        $response->assertActionSuccess();

        $this->assertSame($data['role'], $response->json('data.role'));
        $this->assertSame($data['stipend'], $response->json('data.stipend'));
        $this->assertSame($data['staff_hash'], $response->json('data.staff.hash'));
    }

    public function testCanDetachCrewFromDeparture()
    {
        $departure = $this->getDeparture();
        /** @var \App\Models\Departure\Crew $crew */
        $crew = $departure->crews()->inRandomOrder()->first();

        $this->assertDatabaseHas('departure_crews', [
            'departure_id' => $departure->id,
            'staff_id' => $crew->staff_id,
            'id' => $crew->id,
        ]);

        $response = $this->deleteJson(route('api.departure.crew.detach', [
            'departure_hash' => $departure->hash,
            'crew_hash' => $crew->hash,
        ]));

        $response->assertActionSuccess();
        $this->assertDatabaseMissing('departure_crews', [
            'departure_id' => $departure->id,
            'staff_id' => $crew->staff_id,
            'id' => $crew->id,
        ]);
    }
}
