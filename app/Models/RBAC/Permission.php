<?php

namespace App\Models\RBAC;

use Spatie\Permission\Models\Permission as BasePermission;

class Permission extends BasePermission
{
    protected $hidden = [
        'pivot',
    ];
}
