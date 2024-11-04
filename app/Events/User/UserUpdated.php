<?php

namespace App\Events\User;

use App\Models\User;

class UserUpdated
{
    public function __construct(
        public User $user
    ) {
    }
}
