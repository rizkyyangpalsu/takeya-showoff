<?php

namespace App\Events\User;

use App\Models\User;

class UserDeleted
{
    /**
     * User instance.
     *
     * @var \App\Models\User
     */
    public User $user;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
