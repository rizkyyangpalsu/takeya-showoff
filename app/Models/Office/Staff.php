<?php

namespace App\Models\Office;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class Staff extends User
{
    /** {@inheritdoc} */
    public static function boot()
    {
        parent::boot();

        static::addGlobalScope('staff', function (Builder $builder) {
            $builder->whereIn('user_type', self::getStaffTypes());
        });
    }

    public static function getStaffTypes(): array
    {
        return [
            User::USER_TYPE_SUPER_ADMIN,
            User::USER_TYPE_ADMIN,
            User::USER_TYPE_STAFF_BUS,
            User::USER_TYPE_STAFF_WAREHOUSE,
            User::USER_TYPE_STAFF_WAREHOUSE_DRIVER,
            User::USER_TYPE_STAFF_WORKSHOP,
        ];
    }
}
