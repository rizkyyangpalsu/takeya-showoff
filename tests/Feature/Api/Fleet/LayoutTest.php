<?php

namespace Tests\Feature\Api\Fleet;

use Tests\TestCase;
use App\Models\Fleet\Layout;
use Database\Seeders\LayoutSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LayoutTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public const MODEL_STRUCTURE = [
        'name',
        'description',
        'created_at',
        'updated_at',
        'hash',
    ];

    public bool $seed = true;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCanGetLayout()
    {
        $this->seed(LayoutSeeder::class);

        $this->actingAs($this->getUser());

        $response = $this->get(route('api.fleet.layout'), [
            'Accept' => 'application/json',
        ]);

        $response->assertJsonStructureIsFullPaginate();

        $response->assertJsonStructure([
            'data' => [self::MODEL_STRUCTURE],
        ]);
    }

    public function testCanGetDetailLayout()
    {
        $this->seed(LayoutSeeder::class);

        $this->actingAs($this->getUser());

        /** @var Layout $layout */
        $layout = Layout::query()->first();

        $response = $this->get(route('api.fleet.layout.show', ['layout_hash' => $layout->hash]));

        $response->assertOk();

        $response->assertJsonStructure([
            'code',
            'message',
            'data' => [
                'name',
                'description',
                'hash',
                'created_at',
                'updated_at',
                'seats' => [
                    [
                        'name',
                        'selectable',
                        'plot' => [
                            'x', 'y', 'w', 'h',
                        ],
                        'created_at',
                        'updated_at',
                        'hash',
                    ],
                ],
            ],
        ]);
    }

    public function testCanUpdateLayout()
    {
        $this->seed(LayoutSeeder::class);

        $this->actingAs($this->getUser());

        /** @var Layout $layout */
        $layout = Layout::query()->with(['seats' => fn ($q) => $q->take(20)])->first();

        $layout->makeHidden(['created_at', 'updated_at']);
        $layout->seats->each->makeHidden(['created_at', 'updated_at']);

        // change value
        $layout->seats->each(fn (Layout\Seat $seat, $index) => $seat->setAttribute('name', 'kursi-'.($index + 1)));

        $requestData = $layout->toArray();

        $requestData['seats'] = array_merge($requestData['seats'], Layout\Seat::factory(10)->make()->each->makeHidden('hash')->toArray());

        $response = $this->putJson(route('api.fleet.layout.update', ['layout_hash' => $layout->hash]), $requestData);

        $response->assertActionSuccess();
    }
}
