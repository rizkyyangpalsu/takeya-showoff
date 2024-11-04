<?php

namespace App\Jobs\Fleet;

use App\Models\Fleet;
use App\Models\Office;
use Illuminate\Support\Arr;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Bus\Dispatchable;
use Veelasky\LaravelHashId\Rules\ExistsByHash;

class CreateNewFleet
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Fleet instance.
     *
     * @var \App\Models\Fleet
     */
    public Fleet $fleet;

    /**
     * Filtered attribute.
     *
     * @var array
     */
    public array $attributes;

    /**
     * CreateNewFleet constructor.
     *
     * @param array $attributes
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = Validator::make($attributes, [
            'base_hash' => ['required', new ExistsByHash(Office::class)],
            'layout_hash' => ['required', new ExistsByHash(Fleet\Layout::class)],
            'manufacturer' => 'required',
            'license_plate' => 'required',
            'license_expired_at' => 'nullable|date',
            'hull_number' => 'required',
            'engine_number' => 'nullable',
            'chassis_number' => 'nullable',
            'model' => 'nullable',
            'capacity' => 'nullable',
            'mileage' => 'nullable',
            'purchased_at' => 'nullable|date',
            'is_operable' => 'boolean',
            'specs' => 'nullable|array',
        ])->validate();

        $this->fleet = new Fleet();
    }

    /**
     * Handle job.
     *
     * @return bool
     */
    public function handle()
    {
        $this->attributes['specs'] = $this->attributes['specs'] ?? [];

        $this->fleet->fill(Arr::except($this->attributes, ['base_hash', 'layout_hash']));
        $this->fleet->base()->associate(Office::byHash($this->attributes['base_hash']));
        $this->fleet->layout()->associate(Fleet\Layout::byHash($this->attributes['layout_hash']));

        return $this->fleet->save();
    }
}
