<?php

namespace App\Models\RBAC;

use Spatie\Permission\Models\Role as BaseRole;

class Role extends BaseRole
{
    protected $hidden = [
        'pivot',
    ];
}
