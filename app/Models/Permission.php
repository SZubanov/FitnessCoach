<?php

namespace App\Models;

class Permission extends \Spatie\Permission\Models\Permission
{
    public const SHOW_USER = 'show-users';
    public const CREATE_USER = 'create-users';
    public const UPDATE_USER = 'update-users';
    public const DELETE_USER = 'delete-users';

    public static array $roles = [
        self::SHOW_USER,
        self::CREATE_USER,
        self::UPDATE_USER,
        self::DELETE_USER,
    ];
}
