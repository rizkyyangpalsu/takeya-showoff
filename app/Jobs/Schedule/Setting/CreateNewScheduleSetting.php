<?php

namespace App\Jobs\Schedule\Setting;

use App\Models\Route;
use App\Models\Schedule;
use Illuminate\Bus\Queueable;
use Illuminate\Validation\Rule;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Validation\ValidationException;
use Veelasky\LaravelHashId\Rules\ExistsByHash;

class CreateNewScheduleSetting
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Schedule\Setting
     */
    public Schedule\Setting $setting;

    private array $attributes;

    /**
     * CreateNewScheduleSetting constructor.
     *
     * @param array $attributes
     * @throws ValidationException
     */
    public function __construct(array $attributes)
    {
        ['route_hash' => $routeHash] = Validator::make($attributes, [
            'route_hash' => ['required', new ExistsByHash(Route::class)],
        ])->validate();

        $this->attributes = Validator::make($attributes, [
            'name' => 'required',
            'priority' => [
                'nullable',
                Rule::unique('schedule_settings', 'priority')
                    ->where('route_id', Route::hashToId($routeHash)),
            ],
            'started_at' => 'nullable|date',
            'expired_at' => 'nullable|date',
            'options.days' => 'required|array',
            'options.days.*' => 'required|integer|between:1,7',
        ])->validate();

        $this->attributes['route_hash'] = $routeHash;

        $this->setting = new Schedule\Setting();
    }

    public function handle(): void
    {
        if (! array_key_exists('priority', $this->attributes)) {
            /** @var Schedule\Setting|null $setting */
            $setting = Schedule\Setting::query()
                ->orderByDesc('priority')
                ->select('priority')
                ->first();

            $this->attributes['priority'] = ($setting?->priority ?? 0) + 1;
        }

        $this->setting->fill($this->attributes);
        $this->setting->route()->associate(Route::hashToId($this->attributes['route_hash']));
        $this->setting->save();
    }
}
