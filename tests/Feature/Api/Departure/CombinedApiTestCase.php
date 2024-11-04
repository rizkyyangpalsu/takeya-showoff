<?php

namespace Tests\Feature\Api\Departure;

use App\Models\Departure\Combined;
use Database\Seeders\DepartureCombinedSeeder;

class CombinedApiTestCase extends DepartureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->getStaffAdmin());
        $this->seed(DepartureCombinedSeeder::class);
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCanGetCombinedIndex()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function testCanGetCombinedView()
    {
        $combined = Combined::query()->inRandomOrder()->first();

        $response = $this->get(route('api.departure.combined.show', [
            'combined_hash' => $combined->hash,
        ]));

        $response->assertStatus(200);
    }
}
