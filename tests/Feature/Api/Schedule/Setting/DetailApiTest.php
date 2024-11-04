<?php

namespace Tests\Feature\Api\Schedule\Setting;

use Tests\TestCase;
use App\Models\Fleet\Layout;
use App\Models\Schedule\Setting;
use Database\Seeders\ScheduleSettingSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DetailApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public bool $seed = true;

    public function testCanGetAllSettingDetail()
    {
        $this->actingAs($this->getUser());
        $this->seed(ScheduleSettingSeeder::class);

        /** @var Setting $setting */
        $setting = Setting::query()->first();

        $response = $this->get(route('api.schedule.setting.detail', [
            'setting_hash' => $setting->hash,
        ]));

        $response->assertJsonStructureIsFullPaginate();
    }

    public function testCanGetDetail()
    {
        $this->actingAs($this->getUser());
        $this->seed(ScheduleSettingSeeder::class);

        /** @var Setting $setting */
        $setting = Setting::query()->first();

        /** @var Setting\Detail $detail */
        $detail = $setting->details->first();

        $response = $this->get(route('api.schedule.setting.detail.show', [
            'setting_hash' => $setting->hash,
            'setting_detail_hash' => $detail->hash,
        ]));

        $response->assertOk();

        $response->assertJsonStructure([
            'code',
            'message',
            'data' => [
                'departure',
                'fleet',
                'created_at',
                'updated_at',
                'hash',
            ],
        ]);
    }

    public function testCanCreateNewDetail()
    {
        $this->actingAs($this->getUser());
        $this->seed(ScheduleSettingSeeder::class);

        /** @var Setting $setting */
        $setting = Setting::query()->first();

        /** @var Layout $layout */
        $layout = Layout::query()->whereNotIn('id', $setting->details->map->layout_id)->first();

        $response = $this->postJson(route('api.schedule.setting.detail.store', [
            'setting_hash' => $setting->hash,
        ]), [
            'layout_hash' => $layout->hash,
            'departure' => '18:00',
            'fleet' => 2,
        ]);

        $response->assertActionSuccess();
    }

    public function testCanUpdateDetail()
    {
        $this->actingAs($this->getUser());
        $this->seed(ScheduleSettingSeeder::class);

        /** @var Setting $setting */
        $setting = Setting::query()->first();

        /** @var Setting\Detail $detail */
        $detail = $setting->details->first();

        /** @var Layout $layout */
        $layout = Layout::query()->whereNotIn('id', $setting->details->map->layout_id)->first();

        $response = $this->putJson(route('api.schedule.setting.detail.update', [
            'setting_hash' => $setting->hash,
            'setting_detail_hash' => $detail->hash,
        ]), [
            'layout_hash' => $layout->hash,
            'departure' => '18:00',
            'fleet' => 2,
        ]);

        $response->assertActionSuccess();

        self::assertEquals($layout->id, $detail->fresh(['layout'])->layout_id);
    }

    public function testCanGetSeatsStateOfSettingDetail()
    {
        $this->actingAs($this->getUser());
        $this->seed(ScheduleSettingSeeder::class);

        /** @var Setting $setting */
        $setting = Setting::query()->first();

        /** @var Setting\Detail $detail */
        $detail = $setting->details->first();

        $response = $this->getJson(route('api.schedule.setting.detail.seats-state', [
            'setting_hash' => $setting->hash,
            'setting_detail_hash' => $detail->hash,
        ]));

        $response->assertActionSuccess();
    }

    public function testUpdateCanUpdateDetailSeatConfiguration()
    {
        $this->actingAs($this->getUser());
        $this->seed(ScheduleSettingSeeder::class);

        /** @var Setting $setting */
        $setting = Setting::query()->first();

        /** @var Setting\Detail $detail */
        $detail = $setting->details->first();

        $response = $this->getJson(route('api.schedule.setting.detail.seats-state', [
            'setting_hash' => $setting->hash,
            'setting_detail_hash' => $detail->hash,
        ]));

        $response->assertActionSuccess();

        // make 8 seats reserved
        $response = $this->patchJson(route('api.schedule.setting.detail.seats-state.update', [
            'setting_hash' => $setting->hash,
            'setting_detail_hash' => $detail->hash,
        ]), [
            'seat_configuration' => [
                'reserved' => collect($response->json('data'))->take(8)->map->hash->toArray(),
            ],
        ]);

        $response->assertActionSuccess();

        $response = $this->getJson(route('api.schedule.setting.detail.seats-state', [
            'setting_hash' => $setting->hash,
            'setting_detail_hash' => $detail->hash,
        ]));

        $response->assertActionSuccess();

        self::assertEquals(
            collect(range(0, 7))->map(fn () => 'reserved')->toArray(),
            collect($response->json('data'))->take(8)->map->status->toArray()
        );

        // make 4 seats in front available
        $response = $this->patchJson(route('api.schedule.setting.detail.seats-state.update', [
            'setting_hash' => $setting->hash,
            'setting_detail_hash' => $detail->hash,
        ]), [
            'seat_configuration' => [
                'available' => collect($response->json('data'))->take(4)->map->hash->toArray(),
            ],
        ]);

        $response->assertActionSuccess();

        $response = $this->getJson(route('api.schedule.setting.detail.seats-state', [
            'setting_hash' => $setting->hash,
            'setting_detail_hash' => $detail->hash,
        ]));

        $response->assertActionSuccess();

        self::assertEquals(
            collect(range(0, 3))->map(fn () => 'available')->toArray(),
            collect($response->json('data'))->take(4)->map->status->toArray()
        );

        self::assertEquals(
            collect(range(0, 3))->map(fn () => 'reserved')->toArray(),
            collect($response->json('data'))->splice(4, 4)->map->status->toArray()
        );
    }

    public function testCanDeleteDetail()
    {
        $this->actingAs($this->getUser());
        $this->seed(ScheduleSettingSeeder::class);

        /** @var Setting $setting */
        $setting = Setting::query()->first();

        /** @var Setting\Detail $detail */
        $detail = $setting->details->first();

        $response = $this->delete(route('api.schedule.setting.detail.destroy', [
            'setting_hash' => $setting->hash,
            'setting_detail_hash' => $detail->hash,
        ]));

        $response->assertActionSuccess();

        self::assertEmpty($setting->details()->get());
    }
}
