<?php

namespace Tests\Feature\Api\Departure;

use App\Models\User;
use App\Models\Office;
use App\Models\Departure;
use Database\Seeders\AllowancesTableSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AllowanceApiTest extends DepartureTestCase
{
    use RefreshDatabase, WithFaker;

    public bool $seed = true;

    public function testCanGetAllowancesFromDeparture()
    {
        $count = Departure::query()->count();

        $this->assertDatabaseCount('departure_allowances', $count * AllowancesTableSeeder::$ALLOWANCE_COUNT);

        $departure = $this->getDeparture();

        $response = $this->get(route('api.departure.allowance', [
            'departure_hash' => $departure->hash,
        ]));

        $response->assertJsonStructureIsFullPaginate();
        $response->assertJsonCount(1, 'data');
    }

    public function testCanAddAllowanceToDeparture()
    {
        $count = Departure::query()->count();

        $allowancesCount = $count * AllowancesTableSeeder::$ALLOWANCE_COUNT;

        $this->assertDatabaseCount('departure_allowances', $allowancesCount);

        $departure = $this->getDeparture();
        $office = $this->getOffice();
        $executor = $this->getStaff();
        $receiver = $this->getStaff([$executor->id]);

        $response = $this->postJson(route('api.departure.allowance.store', [
            'departure_hash' => $departure->hash,
        ]), [
            'office_hash' => $office->hash,
            'executor_hash' => $executor->hash,
            'receiver_hash' => $receiver->hash,
            'name' => $this->faker->text(40),
            'description' => $this->faker->text(),
            'amount' => $this->faker->randomNumber(7),
        ]);

        $response->assertActionSuccess();

        $this->assertDatabaseCount('departure_allowances', $allowancesCount + 1);
    }

    public function testCanUpdateExistingAllowance()
    {
        $count = Departure::query()->count();

        /** @var \App\Models\Departure\Allowance $allowance */
        $allowance = Departure\Allowance::query()->inRandomOrder()->first();

        $office = $this->getOffice();
        $executor = $this->getStaff();
        $receiver = $this->getStaff([$executor->id]);

        $data = array_merge($allowance->only(['name', 'description', 'amount']), [
            'office_hash' => $office->hash,
            'executor_hash' => $executor->hash,
            'receiver_hash' => $receiver->hash,
            'name' => $this->faker->text(40),
            'description' => $this->faker->text(),
            'amount' => $this->faker->randomNumber(7),
        ]);

        $response = $this->putJson(route('api.departure.allowance.update', [
            'departure_hash' => $allowance->departure->hash,
            'allowance_hash' => $allowance->hash,
        ]), $data);

        $response->assertActionSuccess();

        $this->assertDatabaseCount('departure_allowances', $count * AllowancesTableSeeder::$ALLOWANCE_COUNT);

        $this->assertSame($data['office_hash'], $response->json('data.office.hash'));
        $this->assertSame(Office\Staff::idToHash(User::hashToId($data['executor_hash'])), $response->json('data.executor.hash'));
        $this->assertSame(Office\Staff::idToHash(User::hashToId($data['receiver_hash'])), $response->json('data.receiver.hash'));
        $this->assertSame($data['name'], $response->json('data.name'));
        $this->assertSame($data['description'], $response->json('data.description'));
        $this->assertSame($data['amount'], (int) $response->json('data.amount'));
    }

    public function testCanDeleteAllowance()
    {
        $count = Departure::query()->count();

        $this->assertDatabaseCount('departure_allowances', $count * AllowancesTableSeeder::$ALLOWANCE_COUNT);

        /** @var \App\Models\Departure\Allowance $allowance */
        $allowance = Departure\Allowance::query()->first();
        $response = $this->deleteJson(route('api.departure.allowance.destroy', [
            'departure_hash' => $allowance->departure->hash,
            'allowance_hash' => $allowance->hash,
        ]));

        $response->assertActionSuccess();

        $this->assertDatabaseMissing('departure_allowances', [
            'id' => $allowance->id,
        ]);
    }
}
