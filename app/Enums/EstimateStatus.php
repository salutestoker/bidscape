<?php

namespace App\Enums;

enum EstimateStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Emailed = 'emailed';
    case Approved = 'approved';
    case Declined = 'declined';
    case Expired = 'expired';
    case SignaturePending = 'signature_pending';
    case Signed = 'signed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::InReview => 'In Review',
            self::Emailed => 'Emailed',
            self::Approved => 'Approved',
            self::Declined => 'Declined',
            self::Expired => 'Expired',
            self::SignaturePending => 'Signature Pending',
            self::Signed => 'Signed',
        };
    }
}
