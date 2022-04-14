<?php

namespace App\Models;

class Role extends \Spatie\Permission\Models\Role
{
    public const ADMIN_ROLE = 'admin';
    public const USER_ROLE = 'user';

    public static array $roles = [
        self::ADMIN_ROLE,
        self::USER_ROLE
    ];
}
