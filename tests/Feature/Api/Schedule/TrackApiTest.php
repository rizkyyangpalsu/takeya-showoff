<?php

namespace Tests\Feature\Api\Schedule;

use Tests\TestCase;
use App\Models\Route;
use Illuminate\Support\Arr;
use App\Models\Route\Track\Point;
use Database\Seeders\PointsTableSeeder;
use Database\Seeders\ScheduleSettingSeeder;
use Database\Seeders\RouteTracksTableSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Schedule\Setting\Detail\PriceModifier;

class TrackApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public const MODEL_STRUCTURE = [
        'name',
        'created_at',
        'updated_at',
        'hash',
        'tracks_count',
        'points_count',
        'tracks' => [
            [
                'origin' => [
                    'code',
                    'name',
                    'terminal',
                    'hash',
                ],
                'destination' => [
                    'code',
                    'name',
                    'terminal',
                    'hash',
                ],
                'duration',
                'destination_transit_duration',
            ],
        ],
        'prices_table' => [
            [
                'origin' => [
                    'code',
                    'name',
                    'terminal',
                    'hash',
                ],
                'destinations' => [
                    [
                        'destination' => [
                            'code',
                            'name',
                            'terminal',
                            'hash',
                        ],
                        'nominal',
                    ],
                ],
            ],
        ],
    ];

    public bool $seed = true;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCanGetTrackRoutes()
    {
        $this->seed(RouteTracksTableSeeder::class);
        $this->actingAs($this->getUser());

        $response = $this->get(route('api.schedule.track-route'));

        $response->assertJsonStructureIsFullPaginate();

        $response->assertJsonStructure([
            'data' => [
                Arr::only(self::MODEL_STRUCTURE, ['name', 'tracks_count', 'points_count', 'created_at', 'updated_at', 'hash']),
            ],
        ]);
    }

    public function testCanGetDetailTrackRoute()
    {
        $this->seed(RouteTracksTableSeeder::class);
        $this->actingAs($this->getUser());

        /**
         * @var $route Route
         */
        $route = Route::query()->first();

        $response = $this->get(route('api.schedule.track-route.show', ['route_hash' => $route->hash]));

        $response->assertJsonCount(Point::query()->count(), 'data.prices_table');

        $response->assertJsonStructure([
            'code',
            'message',
            'data' => self::MODEL_STRUCTURE,
        ]);
    }

    public function testCanCreateRoute()
    {
        $this->seed(PointsTableSeeder::class);
        $this->actingAs($this->getUser());

        $points = Point::all();
        $tracks = $points->map(function (Point $point, int $index) use ($points) {
            $nextPoint = $points->get($index + 1);
            if (! $nextPoint) {
                return $nextPoint;
            }

            return [
                'origin_hash' => $point->hash,
                'destination_hash' => $points->get($index + 1)->hash,
                'duration' => $this->faker->randomElement([30, 120, 33, 12]),
                'destination_transit_duration' => $this->faker->randomElement([10 , 30, 60, 600]),
            ];
        })->filter(fn ($data) => $data);

        $prices = $points->map(fn (Point $point, int $index) => [
            'origin_hash' => $point->hash,
            'destinations' => $points->slice($index + 1)
                ->map(fn (Point $point) => [
                    'hash' => $point->hash,
                    'nominal' => $this->faker->randomElement([150000, 200000, 4000000, 112000]),
                ])->values(),
        ])->filter(fn ($data) => $data['destinations']->count() > 0);

        $response = $this->postJson(route('api.schedule.track-route.store'), [
            'name' => $this->faker->citySuffix.' - '.$this->faker->citySuffix,
            'tracks' => $tracks,
            'prices' => $prices,
        ]);

        $response->assertActionSuccess();

        $response->assertJsonStructure([
            'data' => self::MODEL_STRUCTURE,
        ]);

        $prices->each(fn (array $row) => collect($row['destinations'])
            ->each(fn (array $col) => $this->assertDatabaseHas('prices', [
                'origin_id' => Point::hashToId($row['origin_hash']),
                'destination_id' => Point::hashToId($col['hash']),
            ])));

        self::assertNotSame(Route\Price::query()->count(), 0);
    }

    public function testCanUpdateRoute()
    {
        $this->seed(ScheduleSettingSeeder::class);
        $this->actingAs($this->getUser());

        $route = $this->getRoute();

        $decreasedPoint = 2;

        $points = Point::query()->take(Point::query()->count() - $decreasedPoint)->inRandomOrder()->get();
        $tracks = $points->map(function (Point $point, int $index) use ($points) {
            $nextPoint = $points->get($index + 1);
            if (! $nextPoint) {
                return $nextPoint;
            }

            return [
                'origin_hash' => $point->hash,
                'destination_hash' => $points->get($index + 1)->hash,
                'duration' => $this->faker->randomElement([30, 120, 33, 12]),
                'destination_transit_duration' => $this->faker->randomElement([10 , 30, 60, 600]),
            ];
        })->filter(fn ($data) => $data);

        $prices = $points->map(fn (Point $point, int $index) => [
            'origin_hash' => $point->hash,
            'destinations' => $points->slice($index + 1)
                ->map(fn (Point $point) => [
                    'hash' => $point->hash,
                    'nominal' => $this->faker->randomElement([150000, 200000, 4000000, 112000]),
                ])->values(),
        ])->filter(fn ($data) => $data['destinations']->count() > 0);

        $response = $this->putJson(
            route('api.schedule.track-route.update', ['route_hash' => $route->hash]),
            [
                'name' => $this->faker->citySuffix.' - '.$this->faker->citySuffix,
                'tracks' => $tracks->toArray(),
                'prices' => $prices->toArray(),
            ],
            ['Accept' => 'application/json']
        );

        $response->assertActionSuccess();

        $response->assertJsonStructure([
            'data' => self::MODEL_STRUCTURE,
        ]);

        $tracks->each(fn (array $data, int $index) => $this->assertDatabaseHas('tracks', [
            'origin_id' => Point::hashToId($data['origin_hash']),
            'destination_id' => Point::hashToId($data['destination_hash']),
            'index' => $index,
            'route_id' => Route::hashToId($response->json('data.hash')),
        ]));

        self::assertEquals($tracks->count(), Route::byHash($response->json('data.hash'))->tracks()->count());

        $prices->each(fn (array $row) => collect($row['destinations'])
            ->each(fn (array $col) => $this->assertDatabaseHas('prices', [
                'origin_id' => Point::hashToId($row['origin_hash']),
                'destination_id' => Point::hashToId($col['hash']),
                'nominal' => $col['nominal'],
            ])));

        $totalPrice = $route->prices()->count();

        $route->priceModifiers()->cursor()->each(fn (PriceModifier $priceModifier) => $this->assertSame($totalPrice, $priceModifier->items()->count()));
    }

    public function testCanDeleteRoute()
    {
        $this->seed(RouteTracksTableSeeder::class);
        $this->actingAs($this->getUser());

        $route = $this->getRoute();

        $response = $this->deleteJson(
            route('api.schedule.track-route.destroy', ['route_hash' => $route->hash]),
            [],
            ['Accept' => 'application/json']
        );

        $response->assertActionSuccess();
    }

    private function getRoute(): Route
    {
        /** @var $route \App\Models\Route */
        $route = Route::query()->with(['tracks.origin', 'tracks.destination'])->first();

        self::assertNotNull($route);

        return $route;
    }
}
