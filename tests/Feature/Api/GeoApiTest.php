<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\GeoController;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GeoApiTest extends TestCase
{
    use RefreshDatabase;

    public bool $seed = true;

    public function testCanGetCountry()
    {
        $this->actingAs($this->getUser());

        // test get all
        $response = $this->get(action([GeoController::class, 'index'], ['geo_name' => 'country']));
        $response->assertJsonStructureIsFullPaginate();
        $response->assertJsonCount(15, 'data');

        // test can filter
        $response = $this->get(action([GeoController::class, 'index'], ['geo_name' => 'country', 'keyword' => 'IDN']));
        $response->assertJsonCount(1, 'data');
    }

    public function testCanGetProvince()
    {
        $this->actingAs($this->getUser());

        // test get all
        $response = $this->get(action([GeoController::class, 'index'], ['geo_name' => 'province']));
        $response->assertJsonStructureIsFullPaginate();
        $response->assertJsonCount(15, 'data');

        // test can filter
        $response = $this->get(action([GeoController::class, 'index'], ['geo_name' => 'province', 'keyword' => 'Jawa Timur']));
        $response->assertJsonCount(1, 'data');
    }

    public function testCanGetRegencies()
    {
        $this->actingAs($this->getUser());

        // test get all
        $response = $this->get(action([GeoController::class, 'index'], ['geo_name' => 'regency']));
        $response->assertJsonStructureIsFullPaginate();
        $response->assertJsonCount(15, 'data');

        // test can filter
        $response = $this->get(action([GeoController::class, 'index'], ['geo_name' => 'regency', 'keyword' => 'Surabaya']));
        $response->assertJsonCount(1, 'data');
    }
}
