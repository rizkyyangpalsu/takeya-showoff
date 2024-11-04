<?php

namespace App\Jobs\Users;

use App\Models\User;
use App\Models\Office;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Arr;
use Illuminate\Bus\Queueable;
use Illuminate\Validation\Rule;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateNewUser
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * User instance.
     *
     * @var \App\Models\User
     */
    public User $user;

    /**
     * User attributes.
     *
     * @var array
     */
    public array $attributes;

    /**
     * Create a new job instance.
     *
     * @param array  $attributes
     *
     * @param string $type
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function __construct(array $attributes, string $type = User::USER_TYPE_CUSTOMER)
    {
        $attributes['user_type'] = $type;
        $this->attributes = Validator::make($attributes, [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed',
            'additional_data' => 'nullable|array',
            'user_type' => ['required', Rule::in(User::getUserTypes())],
        ])->validate();

        if (array_key_exists('office_hash', $attributes)) {
            $this->attributes['office_id'] = Office::hashToId($attributes['office_hash']);
        }

        $this->user = new User(Arr::except($this->attributes, 'office_id'));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->user->save();

        if (array_key_exists('office_id', $this->attributes)) {
            $this->user->original_offices()->attach($this->attributes['office_id']);
        }

        event(new Registered($this->user));
    }
}
