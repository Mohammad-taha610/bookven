<?php

namespace App\Enums;

enum UserRole: string
{
    case User = 'user';
    case Manager = 'manager';
    case Admin = 'admin';
    case SuperAdmin = 'super_admin';

    public static function staffRoles(): array
    {
        return [self::Manager, self::Admin, self::SuperAdmin];
    }

    public function isStaff(): bool
    {
        return in_array($this, self::staffRoles(), true);
    }
}
