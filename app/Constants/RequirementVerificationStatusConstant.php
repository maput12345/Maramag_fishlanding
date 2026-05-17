<?php

namespace App\Constants;

class RequirementVerificationStatusConstant
{
    public const PENDING = 'Pending';
    public const VERIFIED = 'Verified';
    public const NEEDS_REVISION = 'Needs Revision';
    public const REJECTED = 'Rejected';

    public static function all(): array
    {
        return [
            self::PENDING,
            self::VERIFIED,
            self::NEEDS_REVISION,
            self::REJECTED,
        ];
    }
}
