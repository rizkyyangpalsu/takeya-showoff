<?php

namespace App\Jobs\Schedule\Setting\Detail;

use App\Models\Schedule;
use App\Models\Fleet\Layout;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Veelasky\LaravelHashId\Rules\ExistsByHash;
use App\Jobs\Schedule\Setting\Detail\PriceModifier\CreateNewPriceModifier;

class CreateNewSettingDetail
{
    /**
     * @var Schedule\Setting\Detail
     */
    public Schedule\Setting\Detail $settingDetail;
    private array $attributes;

    /**
     * @var Schedule\Setting
     */
    private Schedule\Setting $setting;

    /**
     * CreateNewSettingDetail constructor.
     * @param Schedule\Setting $setting
     * @param array $attributes
     * @throws ValidationException
     */
    public function __construct(Schedule\Setting $setting, array $attributes)
    {
        $this->attributes = Validator::make($attributes, [
            'layout_hash' => ['required', new ExistsByHash(Layout::class)],
            'departure' => 'required|date_format:H\:i',
            'fleet' => 'nullable|integer',
        ])->validate();

        $this->setting = $setting;
        $this->settingDetail = new Schedule\Setting\Detail();
    }

    public function handle()
    {
        $this->settingDetail->fill($this->attributes);
        $this->settingDetail->layout()->associate(Layout::hashToId($this->attributes['layout_hash']));
        $this->settingDetail->setting()->associate($this->setting);

        $this->settingDetail->save();

        CreateNewPriceModifier::dispatch($this->settingDetail, [
            'name' => __('primary'),
            'priority' => 1,
        ]);
    }
}
