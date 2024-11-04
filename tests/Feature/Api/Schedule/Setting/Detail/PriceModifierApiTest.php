<?php

namespace Tests\Feature\Api\Schedule\Setting\Detail;

use Tests\TestCase;
use App\Models\Schedule\Setting;
use Database\Seeders\ScheduleSettingSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PriceModifierApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public bool $seed = true;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCanGetListPriceModifier()
    {
        $this->actingAs($this->getUser());
        $this->seed(ScheduleSettingSeeder::class);

        /** @var Setting $setting */
        $setting = Setting::query()->first();

        $response = $this->get(route('api.schedule.setting.detail.price', [
            'setting_hash' => $setting->hash,
            'setting_detail_hash' => $setting->details->first()->hash,
        ]));

        $response->assertJsonStructureIsFullPaginate();
    }

    public function testCanCreatePriceModifier()
    {
        $this->actingAs($this->getUser());
        $this->seed(ScheduleSettingSeeder::class);

        /** @var Setting $setting */
        $setting = Setting::query()->first();

        $response = $this->postJson(route('api.schedule.setting.detail.price', [
            'setting_hash' => $setting->hash,
            'setting_detail_hash' => $setting->details->first()->hash,
        ]), [
            'priority' => 2,
            'name' => 'Weekend',
        ]);

        $response->assertActionSuccess();
    }

    public function testCanGetDetailPriceModifier()
    {
        $this->actingAs($this->getUser());
        $this->seed(ScheduleSettingSeeder::class);

        /** @var Setting $setting */
        $setting = Setting::query()->first();
        /** @var Setting\Detail $settingDetail */
        $settingDetail = $setting->details->first();
        /** @var Setting\Detail\PriceModifier $priceModifier */
        $priceModifier = $settingDetail->priceModifiers->first();

        $response = $this->get(route('api.schedule.setting.detail.price.show', [
            'setting_hash' => $setting->hash,
            'setting_detail_hash' => $settingDetail->hash,
            'price_modifier_hash' => $priceModifier->hash,
        ]));

        $response->assertOk();
    }

    public function testCanUpdatePriceModifier()
    {
        $this->actingAs($this->getUser());
        $this->seed(ScheduleSettingSeeder::class);

        /** @var Setting $setting */
        $setting = Setting::query()->first();
        /** @var Setting\Detail $settingDetail */
        $settingDetail = $setting->details->first();
        /** @var Setting\Detail\PriceModifier $priceModifier */
        $priceModifier = $settingDetail->priceModifiers->first();

        $items = $priceModifier->items
            ->map(fn (Setting\Detail\PriceModifier\Item $item) => [
                'price_hash' => $item->price->hash,
                'amount' => $this->faker->randomElement([10000, 20000, 20003, 40000, 63300]),
            ]);

        $response = $this->putJson(route('api.schedule.setting.detail.price.update', [
            'setting_hash' => $setting->hash,
            'setting_detail_hash' => $settingDetail->hash,
            'price_modifier_hash' => $priceModifier->hash,
        ]), [
            'name' => 'utama',
            'items' => $items,
        ]);

        $response->assertActionSuccess();

        $newItems = $priceModifier->items()->get()
            ->map(fn (Setting\Detail\PriceModifier\Item $item) => [
                'price_hash' => $item->price->hash,
                'amount' => $item->amount,
            ]);

        self::assertEquals($items, $newItems);
    }

    public function testCanUpdatePriceModifierRule()
    {
        $this->actingAs($this->getUser());
        $this->seed(ScheduleSettingSeeder::class);

        /** @var Setting $setting */
        $setting = Setting::query()->first();

        $createNewPriceModResponse = $this->postJson(route('api.schedule.setting.detail.price', [
            'setting_hash' => $setting->hash,
            'setting_detail_hash' => $setting->details->first()->hash,
        ]), [
            'priority' => 2,
            'name' => 'Weekend',
        ]);

        $createNewPriceModResponse->assertActionSuccess();

        $response = $this->patchJson(route('api.schedule.setting.detail.price.update.rule', [
            'setting_hash' => $setting->hash,
            'setting_detail_hash' => $setting->details->first()->hash,
            'price_modifier_hash' => $setting->details->first()->priceModifiers->last()->hash,
        ]), [
            'valid_days' => [1, 2, 3],
        ]);

        $response->assertActionSuccess();
    }
}
