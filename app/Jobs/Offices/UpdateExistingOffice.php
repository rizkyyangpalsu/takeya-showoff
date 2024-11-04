<?php

namespace App\Jobs\Offices;

use App\Models\Office;
use App\Models\Geo\Regency;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Bus\Dispatchable;
use Veelasky\LaravelHashId\Rules\ExistsByHash;

class UpdateExistingOffice
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * filtered attributes.
     *
     * @var array
     */
    public array $attributes;

    /**
     * Office instance.
     *
     * @var \App\Models\Office
     */
    public Office $office;

    /**
     * Create a new job instance.
     *
     * @param \App\Models\Office $office
     * @param array              $attributes
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function __construct(Office $office, array $attributes)
    {
        $this->attributes = Validator::make($attributes, [
            'code' => 'nullable',
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'address' => 'nullable',
            'office_slug' => 'required|unique:offices,slug,'.$office->id,
            'has_workshop' => 'boolean',
            'has_warehouse' => 'boolean',
            'regency_hash' => ['nullable', new ExistsByHash(Regency::class)],
            'parent_hash' => ['nullable', new ExistsByHash(Office::class)],
        ])->validate();

        if (array_key_exists('regency_hash', $this->attributes)) {
            $this->attributes['regency_id'] = Regency::hashToId($this->attributes['regency_hash']);
            unset($this->attributes['regency_hash']);
        }

        if (array_key_exists('parent_hash', $this->attributes)) {
            $this->attributes['office_id'] = Office::hashToId($this->attributes['parent_hash']);
            unset($this->attributes['parent_hash']);
        }

        $this->office = $office;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->office->fill($this->attributes);
        $this->office->save();
    }
}
