<?php

namespace App\Models\Customer;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class Customer extends User
{
    /** {@inheritdoc} */
    public static function boot()
    {
        parent::boot();

        self::addGlobalScope('customer', function (Builder $builder) {
            $builder->where('user_type', User::USER_TYPE_CUSTOMER);
        });

        self::creating(function (User $user) {
            if (! $user->exists) {
                $user->setAttribute('user_type', User::USER_TYPE_CUSTOMER);
            }
        });
    }
}
