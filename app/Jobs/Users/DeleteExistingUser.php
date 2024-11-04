<?php

namespace App\Jobs\Users;

use App\Models\User;
use Illuminate\Bus\Queueable;
use App\Events\User\UserDeleted;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class DeleteExistingUser
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * User instance.
     *
     * @var \App\Models\User
     */
    public User $user;

    /**
     * Create a new job instance.
     *
     * @param \App\Models\User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        $user = $this->user;

        DB::transaction(function () use ($user) {
            event(new UserDeleted($user));

            $user->delete();
        });
    }
}
