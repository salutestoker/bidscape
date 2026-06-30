<?php

namespace App\Enums;

enum ActivityEvent: string
{
    case LeadCreated = 'lead_created';
    case LeadConverted = 'lead_converted';
    case LeadWon = 'lead_won';
    case LeadLost = 'lead_lost';
    case EstimateCreated = 'estimate_created';
    case EstimateEmailed = 'estimate_emailed';
    case EstimateApproved = 'estimate_approved';
    case EstimateDeclined = 'estimate_declined';
    case ContractSent = 'contract_sent';
    case ContractSigned = 'contract_signed';
    case DepositRecorded = 'deposit_recorded';
    case JobCreated = 'job_created';
    case PacketGenerated = 'packet_generated';
    case PacketReady = 'packet_ready';
    case PacketHandedOff = 'packet_handed_off';
    case PricingChanged = 'pricing_changed';
}
