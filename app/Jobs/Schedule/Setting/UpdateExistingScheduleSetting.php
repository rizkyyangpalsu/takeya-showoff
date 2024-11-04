<?php

namespace App\Jobs\Schedule\Setting;

use App\Models\Schedule;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateExistingScheduleSetting
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
    public function __construct(Schedule\Setting $setting, array $attributes)
    {
        $rules = [
            'name' => 'required',
            'priority' => [
                'nullable',
                Rule::unique('schedule_settings', 'priority')
                    ->where('route_id', $setting->route_id)
                    ->ignoreModel($setting),
            ],
            'started_at' => 'nullable|date',
            'expired_at' => 'nullable|date',
            'options.days' => 'required|array',
            'options.days.*' => 'required|numeric|between:1,7',
        ];

        $this->attributes = Validator::make($attributes, $rules)->validate();

        $this->setting = $setting;
    }

    public function handle(): void
    {
        $this->setting->fill($this->attributes);

        $this->setting->save();
    }
}
