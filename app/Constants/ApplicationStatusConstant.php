<?php

namespace App\Constants;

class ApplicationStatusConstant
{
    public const PENDING = 'Pending';
    public const FOR_REVIEW = 'For Review';
    public const SUBMITTED = 'Submitted';
    public const UNDER_REVIEW = 'Under Review';
    public const NEEDS_REVIEW = 'Needs Review';
    public const NEEDS_REVISION = 'Needs Revision';
    public const REVISION_RESUBMITTED = 'Revision Resubmitted';
    public const FOR_REVISION = 'For Revision';
    public const QUALIFIED = 'Qualified';
    public const WINNER = 'Winner';
    public const APPROVED = 'Approved';
    public const NOT_SELECTED = 'Not Selected';
    public const REJECTED = 'Rejected';
    public const CANCELLED = 'Cancelled';

    public static function ongoingReviewStatuses(): array
    {
        return [
            self::PENDING,
            self::FOR_REVIEW,
            self::SUBMITTED,
            self::UNDER_REVIEW,
            self::NEEDS_REVIEW,
            self::NEEDS_REVISION,
            self::REVISION_RESUBMITTED,
            self::FOR_REVISION,
            self::QUALIFIED,
        ];
    }

    public static function winnerStatuses(): array
    {
        return [
            self::WINNER,
            self::APPROVED,
        ];
    }

    public static function notSelectedStatuses(): array
    {
        return [
            self::NOT_SELECTED,
            self::REJECTED,
        ];
    }

    public static function terminalStatuses(): array
    {
        return [
            self::WINNER,
            self::APPROVED,
            self::REJECTED,
            self::NOT_SELECTED,
            self::CANCELLED,
        ];
    }

    public static function reviewStatuses(): array
    {
        return [
            self::UNDER_REVIEW,
            self::NEEDS_REVISION,
            self::REJECTED,
            self::QUALIFIED,
        ];
    }
}
