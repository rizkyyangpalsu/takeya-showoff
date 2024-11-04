<?php

namespace App\Jobs\Users;

use App\Events\User\UserUpdated;
use App\Models\Office;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Validation\Rule;

class UpdateExistingUser
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * User instance.
     *
     * @var \App\Models\User
     */
    public User $user;

    /**
     * @var array
     */
    public array $attributes;

    /**
     * Create a new job instance.
     *
     * @param \App\Models\User $user
     * @param array $attributes
     * @param string|null $type
     * @throws \Illuminate\Validation\ValidationException
     */
    public function __construct(User $user, array $attributes = [], string $type = null)
    {
        $this->user = $user;
        $attributes['user_type'] = $type;
        $this->attributes = Validator::make($attributes, [
            'name' => 'nullable',
            'email' => 'nullable|email',
            'additional_data' => 'nullable|array',
            'password' => 'nullable|confirmed',
            'user_type' => ['nullable', Rule::in(User::getUserTypes())],
        ])->validate();

        if (! $this->attributes['user_type']) {
            unset($this->attributes['user_type']);
        }

        if (array_key_exists('office_hash', $attributes)) {
            $this->attributes['office_id'] = Office::hashToId($attributes['office_hash']);
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->user->update($this->attributes);

        if (array_key_exists('office_id', $this->attributes)) {
            $this->user->original_offices()->sync($this->attributes['office_id']);
        }

        event(new UserUpdated($this->user));
    }
}
