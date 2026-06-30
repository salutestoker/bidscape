<?php

namespace App\Enums;

enum EstimateDeclineReasonType: string
{
    case ReviseBid = 'revise_bid';
    case NoFollowUp = 'no_follow_up';

    public function label(): string
    {
        return match ($this) {
            self::ReviseBid => 'Decline - Revise Bid',
            self::NoFollowUp => 'Decline - No Follow Up',
        };
    }
}
