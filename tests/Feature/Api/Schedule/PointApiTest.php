<?php

namespace Tests\Feature\Api\Schedule;

use Tests\TestCase;
use App\Models\Geo\Regency;
use Illuminate\Support\Arr;
use App\Models\Route\Track\Point;
use Database\Seeders\PointsTableSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PointApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public const MODEL_STRUCTURE = [
        'code',
        'name',
        'terminal',
        'hash',
        'updated_at',
        'created_at',
        'regency' => [
            'name',
            'capital',
            'bsn_code',
            'hash',
        ],
    ];

    public bool $seed = true;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCanGetPoints()
    {
        $this->seed(PointsTableSeeder::class);
        $this->actingAs($this->getUser());

        $response = $this->get(route('api.schedule.point'), [
            'Accept' => 'application/json',
        ]);

        $response->assertJsonStructureIsFullPaginate();

        $response->assertJsonStructure([
            'data' => [
                Arr::except(self::MODEL_STRUCTURE, ['regency']),
            ],
        ]);
    }

    public function testCanGetDetailPoint()
    {
        $this->seed(PointsTableSeeder::class);
        $this->actingAs($this->getUser());

        /** @var Point $point */
        $point = Point::query()->inRandomOrder()->first();

        $response = $this->get(route('api.schedule.point.show', [
            'point_hash' => $point->hash,
        ]));

        $response->assertActionSuccess();

        $response->assertJsonStructure([
            'data' => Arr::except(self::MODEL_STRUCTURE, ['regency']),
        ]);
    }

    public function testCanCreateNewPoint()
    {
        $this->actingAs($this->getUser());

        $regency = Regency::query()->first();

        $response = $this->postJson(route('api.schedule.point.store'), [
            'code' => $this->faker->citySuffix,
            'name' => $this->faker->city,
            'terminal' => $this->faker->text(20),
            'regency_hash' => $regency->hash,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertActionSuccess();

        $response->assertJsonStructure([
            'data' => self::MODEL_STRUCTURE,
        ]);

        self::assertNotNull(Point::byHashOrFail($response->json('data.hash'))->province);
    }

    public function testCanUpdatePoint()
    {
        $this->seed(PointsTableSeeder::class);
        $this->actingAs($this->getUser());

        /** @var Point $point */
        $point = Point::query()->inRandomOrder()->first();
        /** @var Regency $regency */
        $regency = Regency::query()->first();

        $response = $this->putJson(route('api.schedule.point.update', [
            'point_hash' => $point->hash,
        ]), [
            'code' => $point->code,
            'name' => $point->name,
            'terminal' => $point->terminal,
            'regency_hash' => $regency->hash,
        ]);

        $response->assertActionSuccess();
        $response->assertJsonStructure([
            'data' => self::MODEL_STRUCTURE,
        ]);
        self::assertNotNull(Point::byHashOrFail($response->json('data.hash'))->province);
    }

    public function testCanDeletePoint()
    {
        $this->seed(PointsTableSeeder::class);
        $this->actingAs($this->getUser());

        /** @var Point $point */
        $point = Point::query()->inRandomOrder()->first();

        $response = $this->deleteJson(route('api.schedule.point.destroy', ['point_hash' => $point->hash]));

        $response->assertActionSuccess();
    }
}
