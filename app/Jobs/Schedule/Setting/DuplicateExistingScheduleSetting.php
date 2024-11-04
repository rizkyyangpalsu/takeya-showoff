<?php

namespace App\Jobs\Schedule\Setting;

use App\Models\Schedule;
use Illuminate\Validation\ValidationException;

class DuplicateExistingScheduleSetting
{
    /**
     * @var Schedule\Setting
     */
    public Schedule\Setting $setting;
    private array $attributes;

    /**
     * UpdateExistingScheduleSetting constructor.
     * @param Schedule\Setting $setting
     * @param array $attributes
     * @throws ValidationException
     */
    public function __construct(Schedule\Setting $setting)
    {
        $this->setting = $setting;
    }

    public function handle(): void
    {
        $newSetting = $this->setting->replicate();
        $newSetting->name = $this->setting->name.'-copy';
        $newSetting->save();

        $this->setting->details->each(function ($detail) use ($newSetting) {
            $scheduleDetail = $detail->replicate();
            $scheduleDetail->setting_id = $newSetting->id;
            $scheduleDetail->save();
        });
    }
}
