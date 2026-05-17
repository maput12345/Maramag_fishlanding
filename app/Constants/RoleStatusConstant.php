<?php

namespace App\Constants;

class RoleStatusConstant
{
    public const ADMIN = 'admin';
    public const STAFF = 'staff';
    public const BROKER = 'broker';
    public const CASHIER = 'cashier';
    public const APPLICANT = 'applicant';

    public static function getAllRoles(): array
    {
        return [
            self::ADMIN,
            self::STAFF,
            self::BROKER,
            self::CASHIER,
            self::APPLICANT,
        ];
    }

    public static function isValidRole(string $role): bool
    {
        return in_array($role, self::getAllRoles());
    }
}
