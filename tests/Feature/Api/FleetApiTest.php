<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\Fleet;
use Database\Seeders\FleetsTableSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FleetApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public bool $seed = true;

    public function testCanGetAllFleet()
    {
        $this->actingAs($this->getUser());
        $this->seed(FleetsTableSeeder::class);

        $this->assertDatabaseCount('fleets', FleetsTableSeeder::$COUNT);

        $response = $this->get(route('api.fleet'));

        $response->assertJsonStructureIsFullPaginate();
        $this->assertEquals(FleetsTableSeeder::$COUNT, $response->json('total'));
    }

    public function testFilterFleet()
    {
        $this->actingAs($this->getUser());
        $this->seed(FleetsTableSeeder::class);

        $this->assertDatabaseCount('fleets', FleetsTableSeeder::$COUNT);

        /** @var Fleet $fleet */
        $fleet = Fleet::query()->inRandomOrder()->first();

        $response = $this->getJson(route('api.fleet', ['keyword' => $fleet->license_plate]));

        $response->assertJsonCount(1, 'data');

        $response = $this->getJson(route('api.fleet', ['is_operable' => false]));

        $response->assertJsonCount(0, 'data');
    }
}
