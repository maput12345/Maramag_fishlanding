<?php

namespace App\Constants;

class FishBoxStatusConstant
{
    public const UNASSIGNED = 'Unassigned';
    public const IN_STOCK = 'In Stock';
    public const SOLD = 'Sold';
    public const RETURNED = 'Returned';
    public const MISSING = 'Missing';
    public const RETIRED = 'Retired';

    /**
     * @return array
     */
    public static function getAllStatuses(): array
    {
        return [
            self::UNASSIGNED,
            self::IN_STOCK,
            self::SOLD,
            self::RETURNED,
            self::MISSING,
            self::RETIRED,
        ];
    }

    public static function label(string $status): string
    {
        return $status === self::RETIRED ? 'Inactive' : $status;
    }

    public static function getEditableStatuses(): array
    {
        return [
            self::IN_STOCK,
        ];
    }

    public static function getStatusOnlyForAdmin(): array
    {
        return [
            self::RETURNED,
            self::MISSING,
        ];
    }
}
