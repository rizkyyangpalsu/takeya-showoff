<?php

namespace Tests\Feature\Api\Schedule;

use Tests\TestCase;
use App\Models\Route;
use App\Models\Schedule\Setting;
use Database\Seeders\LayoutSeeder;
use Database\Seeders\ScheduleSettingSeeder;
use Database\Seeders\RouteTracksTableSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SettingApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public bool $seed = true;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCanGetScheduleSetting()
    {
        $this->actingAs($this->getUser());
        $this->seed(ScheduleSettingSeeder::class);

        $response = $this->get(route('api.schedule.setting'));

        $response->assertJsonStructureIsFullPaginate();

        $response->assertJsonStructure([
            'data' => [
                [
                    'started_at',
                    'expired_at',
                    'name',
                    'priority',
                    'created_at',
                    'updated_at',
                    'hash',
                    'options' => [
                        'days',
                    ],
                    'route' => [
                        'name',
                        'created_at',
                        'updated_at',
                        'hash',
                    ],
                ],
            ],
        ]);
    }

    public function testCanGetDetailScheduleSetting()
    {
        $this->actingAs($this->getUser());
        $this->seed(ScheduleSettingSeeder::class);

        $response = $this->get(route('api.schedule.setting.show', [
            'setting_hash' => Setting::query()->first()->hash,
        ]));

        $response->assertOk();

        $response->assertJsonStructure([
            'code',
            'message',
            'data' => [
                'started_at',
                'expired_at',
                'name',
                'priority',
                'created_at',
                'updated_at',
                'hash',
                'route' => [
                    'name',
                    'created_at',
                    'updated_at',
                    'hash',
                ],
                'options' => [
                    'days',
                ],
                'details' => [
                    [
                        'departure',
                        'fleet',
                        'created_at',
                        'updated_at',
                        'layout' => [
                            'name',
                            'description',
                            'created_at',
                            'updated_at',
                            'hash',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function testCanCreateScheduleSetting()
    {
        $this->actingAs($this->getUser());
        // prepare dependencies feature
        $this->seed(RouteTracksTableSeeder::class);
        $this->seed(LayoutSeeder::class);

        /** @var $route Route */
        $route = Route::query()->inRandomOrder()->first();

        $data = [
            'name' => 'standard daily schedule',
            'route_hash' => $route->hash,
            'options' => [
                'days' => collect(range(1, 7))->map(fn (int $num) => (string) $num)->toArray(),
            ],
        ];

        $response = $this->postJson(route('api.schedule.setting.store'), $data);

        $response->assertActionSuccess();

        /**
         * @var $setting Setting
         */
        $setting = Setting::query()->find(Setting::hashToId($response->json('data.hash')));

        self::assertEquals($data['route_hash'], $setting->route->hash);
    }

    public function testCanUpdateScheduleSetting()
    {
        $this->actingAs($this->getUser());
        $this->seed(ScheduleSettingSeeder::class);
        $this->seed(RouteTracksTableSeeder::class);

        /** @var Setting $setting */
        $setting = Setting::query()->first();
        /** @var Route $newRoute */
        $newDays = range(1, 4);

        $data = collect($setting->toArray())->forget([
            'created_at',
            'updated_at',
            'hash',
        ]);

        $data->offsetSet('options', ['days' => $newDays]);

        $response = $this->putJson(
            route('api.schedule.setting.update', ['setting_hash' => $setting->hash]),
            $data->toArray()
        );

        $response->assertActionSuccess();
    }

    public function testCanDeleteScheduleSetting()
    {
        $this->actingAs($this->getUser());
        $this->seed(ScheduleSettingSeeder::class);

        /** @var $setting Setting */
        $setting = Setting::query()->first();
        $response = $this->delete(route('api.schedule.setting.destroy', [
            'setting_hash' => $setting->hash,
        ]));

        $response->assertActionSuccess();

        self::assertEquals(
            0,
            Setting\Detail::query()->where('setting_id', $setting->id)->count()
        );
    }
}
