<?php

namespace App\Jobs\Offices;

use App\Models\Office;
use App\Models\Geo\Regency;
use Illuminate\Support\Arr;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Bus\Dispatchable;
use Veelasky\LaravelHashId\Rules\ExistsByHash;

class CreateNewOffice
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Filtered attributes.
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
     * @param array $attributes
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function __construct(array $attributes)
    {
        $this->attributes = Validator::make($attributes, [
            'code' => 'nullable',
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'address' => 'nullable',
            'has_workshop' => 'boolean',
            'has_warehouse' => 'boolean',
            'create_accounting_accounts' => 'boolean',
            'regency_hash' => ['nullable', new ExistsByHash(Regency::class)],
            'parent_hash' => ['nullable', new ExistsByHash(Office::class)],
        ])->validate();

        if (array_key_exists('regency_hash', $this->attributes)) {
            $this->attributes['regency_id'] = Regency::hashToId($this->attributes['regency_hash']);
            unset($this->attributes['regency_hash']);
        }

        if (array_key_exists('parent_hash', $this->attributes)) {
            if (! empty($this->attributes['parent_hash'])) {
                $this->attributes['office_id'] = Office::hashToId($this->attributes['parent_hash']);
            }

            unset($this->attributes['parent_hash']);
        }

        $this->office = new Office(Arr::except($this->attributes, 'create_accounting_accounts'));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->office->save();

        if (Arr::get($this->attributes, 'create_accounting_accounts', false)) {
            dispatch(new CreateOfficeAccounts($this->office->fresh()));
        }
    }
}
