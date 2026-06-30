<?php

namespace App\Enums;

enum LeadStatus: string
{
    case PendingContact = 'pending_contact';
    case SiteVisit = 'site_visit';
    case PendingEstimate = 'pending_estimate';
    case EstimateSent = 'estimate_sent';
    case Approved = 'approved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::PendingContact => 'Pending Contact',
            self::SiteVisit => 'Site Visit',
            self::PendingEstimate => 'Pending Estimate',
            self::EstimateSent => 'Estimate Sent',
            self::Approved => 'Approved',
            self::Closed => 'Closed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PendingContact => 'neutral',
            self::SiteVisit => 'blue',
            self::PendingEstimate => 'amber',
            self::EstimateSent => 'indigo',
            self::Approved => 'green',
            self::Closed => 'red',
        };
    }
}
