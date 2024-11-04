<?php

namespace App\Jobs\Schedule\Setting\Detail;

use App\Models\Fleet\Layout;
use App\Models\Schedule\Setting;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Veelasky\LaravelHashId\Rules\ExistsByHash;

class UpdateExistingSettingDetail
{
    /**
     * @var Setting\Detail
     */
    public Setting\Detail $settingDetail;

    private array $attributes;

    /**
     * @var Setting|null
     */
    private ?Setting $setting;

    /**
     * UpdateExistingSettingDetail constructor.
     * @param Setting\Detail $detail
     * @param array $attributes
     * @param Setting|null $setting
     * @throws ValidationException
     */
    public function __construct(Setting\Detail $detail, array $attributes, ?Setting $setting = null)
    {
        $this->attributes = Validator::make($attributes, [
            'layout_hash' => ['nullable', new ExistsByHash(Layout::class)],
            'departure' => 'nullable|date_format:H\:i',
            'fleet' => 'nullable|integer',
            'seat_configuration' => 'nullable|array',
            'seat_configuration.reserved' => 'nullable|array',
            'seat_configuration.available' => 'nullable|array',
            'seat_configuration.reserved.*' => ['nullable', 'string', new ExistsByHash(Layout\Seat::class)],
            'seat_configuration.available.*' => ['nullable', 'string', new ExistsByHash(Layout\Seat::class)],
        ])->validate();

        $this->settingDetail = $detail;
        $this->setting = $setting;
    }

    public function handle()
    {
        $this->settingDetail->fill($this->attributes);

        if (array_key_exists('layout_hash', $this->attributes)) {
            $this->settingDetail->layout()->associate(Layout::byHashOrFail($this->attributes['layout_hash']));
        }

        if ($this->setting) {
            $this->settingDetail->setting()->associate($this->setting);
        }

        if (array_key_exists('seat_configuration', $this->attributes) &&
            (! empty($this->attributes['seat_configuration']['reserved']) || ! empty($this->attributes['seat_configuration']['available']))
        ) {
            if (! empty($this->attributes['seat_configuration']['reserved'])) {
                $this->settingDetail->seat_configuration
                    ->reserveSeat(...collect($this->attributes['seat_configuration']['reserved'])
                        ->map(fn (string $hash) => Layout\Seat::hashToId($hash))
                        ->toArray());
            }

            if (! empty($this->attributes['seat_configuration']['available'])) {
                $this->settingDetail->seat_configuration
                    ->makeAvailable(collect($this->attributes['seat_configuration']['available'])
                        ->map(fn (string $hash) => Layout\Seat::hashToId($hash))
                        ->toArray());
            }
        }

        $this->settingDetail->save();
    }
}
