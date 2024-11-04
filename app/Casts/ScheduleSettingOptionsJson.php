<?php

namespace App\Casts;

use App\Support\ScheduleSettingOptions;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class ScheduleSettingOptionsJson implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function get($model, string $key, $value, array $attributes)
    {
        $data = json_decode($value, true);

        return new ScheduleSettingOptions($data ?? []);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function set($model, string $key, $value, array $attributes)
    {
        return match (true) {
            $value instanceof ScheduleSettingOptions => $value->toJson(),
            is_array($value) => (new ScheduleSettingOptions($value))->toJson(),
            default => (new ScheduleSettingOptions([]))->toJson(),
        };
    }
}
